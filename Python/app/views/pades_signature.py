import base64
import os
import uuid

from flask import make_response, render_template, request

from lacunarestpki import PadesSignatureStarter, PadesSignatureFinisher, \
    StandardSignaturePolicies
from app import APPDATA_FOLDER, STATIC_FOLDER
from app.blueprints import pades_signature
from app.util import get_restpki_client, get_expired_page_headers, \
    get_pdf_stamp_content, get_security_context_id
from app.util_pades import get_visual_representation_position


@pades_signature.route('/')
@pades_signature.route('/<userfile>')
def index(userfile=None):
    """

    This function initiates a PAdES signature using REST PKI and renders the
    signature page.

    Both PAdES signature examples, with a server file and with a file uploaded
    by the user, converge to this function. The difference is that, when the
    file is uploaded by the user, the function is called with a URL argument
    named "userfile".

    """

    try:

        # Get an instantiate of the PadesSignatureStarter class, responsible for
        # receiving the signature elements and start the signature process.
        signature_starter = PadesSignatureStarter(get_restpki_client())

        # Set the signature policy.
        signature_starter.signature_policy_id = StandardSignaturePolicies.PADES_BASIC

        # Set a security context to be used to determine trust in the
        # certificate chain. We have encapsulated the security context choice on
        # util.py.
        signature_starter.security_context_id = get_security_context_id()

        # If the URL argument "userfile" is filled, it means the user was
        # redirected here by "upload" view (signature with file uploaded by
        # user). We'll set the path of the file to be signed, which was saved
        # in the app_data folder by "upload" view.
        if userfile is not None:
            signature_starter.set_pdf_path('%s/%s' % (APPDATA_FOLDER, userfile))
        else:
            signature_starter.set_pdf_path(
                '%s/%s' % (STATIC_FOLDER, 'SampleDocument.pdf'))

        # Set the visual representation for the signature.
        signature_starter.visual_representation = ({

            'text': {

                # The tags {{name}} and {{national_id}} will be substituted
                # according to the user's certificate:
                #
                #   name        : full name of the signer;
                #   national_id : if the certificate is ICP-Brasil, contains the
                #                 signer's CPF.
                #
                # For a full list of the supported tags, see:
                # https://github.com/LacunaSoftware/RestPkiSamples/blob/master/PadesTags.md
                'text': 'Signed by {{name}} ({{national_id}})',
                # Specify that the signing time should also be rendered.
                'includeSigningTime': True,
                # Optionally set the horizontal alignment of the text ('Left' or
                # 'Right'), if not set the default is Left.
                'horizontalAlign': 'Left'
            },

            'image': {

                # We'll use as background the image in static/PdfStamp.png.
                'resource': {
                    'content': base64.b64encode(get_pdf_stamp_content()),
                    'mimeType': 'image/png'
                },
                # Opacity is an integer from 0 to 100 (0 is completely
                # transparent, 100 is completely opaque).
                'opacity': 50,

                # Align the image to the right.
                'horizontalAlign': 'Right'
            },

            # Position of the visual representation. We have encapsulated this
            # code in a function to include several possibilities depending on
            # the argument passed to the function. Experiment changing the
            # argument to see different examples of signature positioning (valid
            # values are 1-6). Once you decide which is best for your case, you
            # can place the code directly here.
            'position': get_visual_representation_position(1)
        })

        # Call the start_with_webpki() method, which initiates the signature.
        # This yields the token, a 43-character case-sensitive URL-safe string,
        # which identifies this signature process. We'll use this value to call
        # the signWithRestPki() method on the Web PKI component (see
        # signature-form.js javascript) and also to complete the signature after
        # the form is submitted (see method pades_signature_action()). This
        # should not be mistaken with the API access token.
        token = signature_starter.start_with_webpki()

    except Exception as e:
        return render_template('error.html', msg=e)

    response = make_response(render_template('pades_signature/index.html',
                                             token=token, userfile=userfile))

    # The token acquired above can only be used for a single signature attempt.
    # In order to retry the signature it is necessary to get a new token. This
    # can be a problem if the user uses the back button of the browser, since
    # the browser might show a cached page that we rendered previously, with a
    # now stale token. To prevent this from happen, we force page expiration
    # through HTTP headers to prevent caching of the page.
    response.headers = get_expired_page_headers()

    return response


@pades_signature.route('/action', methods=['POST'])
def action():
    """

    This function receives the form submission from the template. We'll call
    REST PKI to complete the signature.

    """

    # Get the token for this signature. (rendered in a hidden input field, see
    # pades-signature.html template)
    token = request.form['token']

    # Get an intance of the PadesSignatureFinisher class, responsible for
    # completing the signature process.
    signature_finisher = PadesSignatureFinisher(get_restpki_client())

    # Set the token.
    signature_finisher.token = token

    # Call the finish() method, which finalizes the signature process and
    # returns the signed PDF.
    signature_finisher.finish()

    # Get information about the certificate used by the user to sign the file.
    # This method must only be called after calling the finish() method.
    signer_cert = signature_finisher.certificate

    # At this point, you'd typically store the signed PDF on your database. For
    # demonstration purposes, we'll store the PDF on a temporary folder publicly
    # accessible and render a link to it.

    filename = '%s.pdf' % (str(uuid.uuid1()))
    signature_finisher.write_signed_pdf(os.path.join(APPDATA_FOLDER, filename))

    return render_template('pades_signature/action.html', filename=filename,
                           signer_cert=signer_cert)

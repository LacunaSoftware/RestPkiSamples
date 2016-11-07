import base64
import os
import uuid

from flask import Blueprint, make_response, render_template, request

from lacunarestpki import PadesSignatureStarter, PadesSignatureFinisher, StandardSignaturePolicies, \
    StandardSecurityContexts, PadesVisualPositioningPresets
from util import restpki_client, get_expired_page_headers
from config import app, STATIC_FOLDER, TEMPLATE_DOCUMENT

blueprint = Blueprint('pades_signature', __name__)


@blueprint.route('/pades-signature')
@blueprint.route('/pades-signature/<userfile>')
def pades_signature(userfile=None):
    # Read the PDF stamp image
    f = open('static/PdfStamp.png', 'rb')
    pdf_stamp = f.read()
    f.close()

    # Instantiate the PadesSignatureStarter class, responsible for receiving the signature elements and start the
    # signature process
    try:
        signature_starter = PadesSignatureStarter(restpki_client)

        # If the user was redirected here by /upload (signature with file uploaded by user), the "userfile" route
        # argument will contain the filename under the app_data/ folder. Otherwise (signature with server file), we'll
        # sign a sample document.
        #
        # Note: The file will be read with the standard open() function, so the same path rules apply.
        if userfile is not None:
            signature_starter.set_pdf_path('%s/%s' % (app.config['APPDATA_FOLDER'], userfile))
        else:
            signature_starter.set_pdf_path('%s/%s' % (STATIC_FOLDER, TEMPLATE_DOCUMENT))

        # Set the signature policy
        signature_starter.signature_policy_id = StandardSignaturePolicies.PADES_BASIC

        # Set a SecurityContext to be used to determine trust in the certificate chain
        signature_starter.security_context_id = StandardSecurityContexts.PKI_BRAZIL
        # Note: By changing the SecurityContext above you can accept only certificates from a certain PKI, for instance,
        # ICP-Brasil (lacunarestpki.StandardSecurityContexts.PKI_BRAZIL).

        # Set the visual representation for the signature
        signature_starter.visual_representation = ({

            'text': {
                # The tags {{signerName}} and {{signerNationalId}} will be substituted according to the user's
                # certificate
                # signerName -> full name of the signer
                # signerNationalId -> if the certificate is ICP-Brasil, contains the signer's CPF
                'text': 'Signed by {{signerName}} ({{signerNationalId}})',
                # Specify that the signing time should also be rendered
                'includeSigningTime': True,
                # Optionally set the horizontal alignment of the text ('Left' or 'Right'), if not set the default is
                # Left
                'horizontalAlign': 'Left'
            },

            'image': {
                # We'll use as background the image that we've read above
                'resource': {
                    'content': base64.b64encode(pdf_stamp),
                    'mimeType': 'image/png'
                },
                # Opacity is an integer from 0 to 100 (0 is completely transparent, 100 is completely opaque).
                'opacity': 50,

                # Align the image to the right
                'horizontalAlign': 'Right'
            },

            # Position of the visual representation. We have encapsulated this code in a function to include several
            # possibilities depending on the argument passed to the function. Experiment changing the argument to see
            # different examples of signature positioning (valid values are 1-6). Once you decide which is best for
            # your case, you can place the code directly here.
            'position': get_visual_representation_position(1)
        })

        # Call the start_with_webpki() method, which initiates the signature. This yields the token, a 43-character
        # case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
        # signWithRestPki() method on the Web PKI component (see lacuna-web-pki-client.js javascript) and also to
        # complete the signature after the form is submitted (see method pades_signature_action())). This should not be
        # mistaken with the API access token.
        token = signature_starter.start_with_webpki()

    except Exception as e:
        return render_template('error.html', msg=e)

    response = make_response(render_template('pades-signature.html', token=token, userfile=userfile))

    # The token acquired above can only be used for a single signature attempt. In order to retry the signature it is
    # necessary to get a new token. This can be a problem if the user uses the back button of the browser, since the
    # browser might show a cached page that we rendered previously, with a now stale token. # we force page expiration
    # through HTTP headers to prevent caching of the page.
    response.headers = get_expired_page_headers()

    return response


@blueprint.route('/pades-signature-action', methods=['POST'])
def pades_signature_action():
    # Get the token for this signature (rendered in a hidden input field, see pades-signature.html template)
    token = request.form['token']

    # Instantiate the PadesSignatureFinisher class, responsible for completing the signature process
    signature_finisher = PadesSignatureFinisher(restpki_client)

    # Set the token
    signature_finisher.token = token

    # Call the finish() method, which finalizes the signature process and returns the signed PDF
    signature_finisher.finish()

    # Get information about the certificate used by the user to sign the file. This method must only be called after
    # calling the finish() method.
    signer_cert = signature_finisher.certificate

    # At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
    # store the PDF on a temporary folder publicly accessible and render a link to it.

    filename = '%s.pdf' % (str(uuid.uuid1()))
    signature_finisher.write_signed_pdf(os.path.join(app.config['APPDATA_FOLDER'], filename))

    return render_template('pades-signature-info.html', filename=filename, signer_cert=signer_cert)


def get_visual_representation_position(sample_number):
    if sample_number == 1:
        # Example #1: automatic positioning on footnote. This will insert the signature, and future signatures,
        # ordered as a footnote of the last page of the document
        return PadesVisualPositioningPresets.get_footnote(restpki_client)
    elif sample_number == 2:
        # Example #2: get the footnote positioning preset and customize it
        visual_position = PadesVisualPositioningPresets.get_footnote(restpki_client)
        visual_position['auto']['container']['left'] = 2.54
        visual_position['auto']['container']['bottom'] = 2.54
        visual_position['auto']['container']['right'] = 2.54
        return visual_position
    elif sample_number == 3:
        # Example #3: automatic positioning on new page. This will insert the signature, and future signatures,
        # in a new page appended to the end of the document.
        return PadesVisualPositioningPresets.get_new_page(restpki_client)
    elif sample_number == 4:
        # Example #4: get the "new page" positioning preset and customize it
        visual_position = PadesVisualPositioningPresets.get_new_page(restpki_client)
        visual_position['auto']['container']['left'] = 2.54
        visual_position['auto']['container']['top'] = 2.54
        visual_position['auto']['container']['right'] = 2.54
        visual_position['auto']['signatureRectangleSize']['width'] = 5
        visual_position['auto']['signatureRectangleSize']['height'] = 3
        return visual_position
    elif sample_number == 5:
        # Example #5: manual positioning
        return {
            'pageNumber': 0,
            # zero means the signature will be placed on a new page appended to the end of the document
            'measurementUnits': 'Centimeters',
            # define a manual position of 5cm x 3cm, positioned at 1 inch from the left and bottom margins
            'manual': {
                'left': 2.54,
                'bottom': 2.54,
                'width': 5,
                'height': 3
            }
        }
    elif sample_number == 6:
        # Example #6: custom auto positioning
        return {
            'pageNumber': -1,
            # negative values represent pages counted from the end of the document (-1 is last page)
            'measurementUnits': 'Centimeters',
            'auto': {
                # Specification of the container where the signatures will be placed, one after the other
                'container': {
                    # Specifying left and right (but no width) results in a variable-width container with the given
                    # margins
                    'left': 2.54,
                    'right': 2.54,
                    # Specifying bottom and height (but no top) results in a bottom-aligned fixed-height container
                    'bottom': 2.54,
                    'height': 12.31
                },
                # Specification of the size of each signature rectangle
                'signatureRectangleSize': {
                    'width': 5,
                    'height': 3
                },
                # The signatures will be placed in the container side by side. If there's no room left, the
                # signatures will "wrap" to the next row. The value below specifies the vertical distance between
                # rows
                'rowSpacing': 1
            }
        }
    else:
        return None

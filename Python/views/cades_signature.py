import os
import uuid

from flask import Blueprint, make_response, render_template, request
from lacunarestpki import CadesSignatureStarter, StandardSignaturePolicies, StandardSecurityContexts, \
    CadesSignatureFinisher

from util import restpki_client, get_expired_page_headers, STATIC_FOLDER, TEMPLATE_DOCUMENT, APPDATA_FOLDER

# Create a blueprint for this view for its routes to be reachable
blueprint = Blueprint('cades_signature', __name__)


@blueprint.route('/cades-signature')
@blueprint.route('/cades-signature/<userfile>')
@blueprint.route('/cades-signature-cosign/<cmsfile>')
def cades_signature(userfile=None, cmsfile=None):
    """
        This function initiates a CAdES signature using REST PKI and renders the signature page.

        All CAdES signature examples converge to this action, but with different URL arguments:

        1. Signature with a server file               : no arguments filled
        2. Signature with a file uploaded by the user : "userfile" filled
        3. Co-signature of a previously signed CMS    : "cmsfile" filled
    """

    # Instantiate the CadesSignatureStarter class, responsible for receiving the signature elements and start the
    # signature process
    try:
        signature_starter = CadesSignatureStarter(restpki_client)

        # If the user was redirected here by /upload (signature with file uploaded by user), the "userfile" route
        # argument will contain the filename under the app_data/ folder. Otherwise (signature with server file), we'll
        # sign a sample document.
        #
        # Note: The file will be read with the standard open() function, so the same path rules apply.
        if cmsfile is not None:
            signature_starter.set_cms_to_cosign_path('%s/%s' % (APPDATA_FOLDER, cmsfile))
        elif userfile is not None:
            signature_starter.set_file_to_sign_path('%s/%s' % (APPDATA_FOLDER, userfile))
        else:
            signature_starter.set_file_to_sign_path('%s/%s' % (STATIC_FOLDER, TEMPLATE_DOCUMENT))

        # Set the signature policy
        signature_starter.signature_policy_id = StandardSignaturePolicies.CADES_ICPBR_ADR_BASICA

        # Set a SecurityContext to be used to determine trust in the certificate chain
        signature_starter.security_context_id = StandardSecurityContexts.PKI_BRAZIL
        # Note: By changing the SecurityContext above you can accept only certificates from a certain PKI, for instance,
        # ICP-Brasil (lacunarestpki.StandardSecurityContexts.PKI_BRAZIL).

        signature_starter.encapsulate_content = True

        # Call the start_with_webpki() method, which initiates the signature. This yields the token, a 43-character
        # case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
        # signWithRestPki() method on the Web PKI component (see lacuna-web-pki-client.js javascript) and also to
        # complete the signature after the form is submitted (see method pades_signature_action())). This should not be
        # mistaken with the API access token.
        token = signature_starter.start_with_webpki()

    except Exception as e:
        return render_template('error.html', msg=e)

    response = make_response(render_template('cades-signature.html', token=token, userfile=userfile, cmsfile=cmsfile))

    # The token acquired above can only be used for a single signature attempt. In order to retry the signature it is
    # necessary to get a new token. This can be a problem if the user uses the back button of the browser, since the
    # browser might show a cached page that we rendered previously, with a now stale token. # we force page expiration
    # through HTTP headers to prevent caching of the page.
    response.headers = get_expired_page_headers()

    return response


@blueprint.route('/cades-signature-action', methods=['POST'])
def cades_signature_action():
    """
        This method receives the form submission from the template. We'll call REST PKI to complete the signature
    """

    # Get the token for this signature (rendered in a hidden input field, see pades-signature.html template)
    token = request.form['token']

    # Instantiate the CadesSignatureFinisher class, responsible for completing the signature process
    signature_finisher = CadesSignatureFinisher(restpki_client)

    # Set the token
    signature_finisher.token = token

    # Call the finish() method, which finalizes the signature process and returns the signed PDF
    signature_finisher.finish()

    # Get information about the certificate used by the user to sign the file. This method must only be called after
    # calling the finish() method.
    signer_cert = signature_finisher.certificate

    # At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
    # store the PDF on a temporary folder publicly accessible and render a link to it.

    filename = '%s.p7s' % (str(uuid.uuid1()))
    signature_finisher.write_cms(os.path.join(APPDATA_FOLDER, filename))

    return render_template('cades-signature-info.html', filename=filename, signer_cert=signer_cert)

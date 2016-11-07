import os
import uuid

from flask import Blueprint, make_response, render_template, request

from lacunarestpki import FullXmlSignatureStarter, NamespaceManager, XmlInsertionOptions, StandardSignaturePolicies, \
    StandardSecurityContexts, XmlElementSignatureStarter, XmlSignatureFinisher
from util import restpki_client, get_expired_page_headers
from config import STATIC_FOLDER, XML_DOCUMENT, NFE_SAMPLE, app

blueprint = Blueprint('xml_signature', __name__)


@blueprint.route('/xml-full-signature')
def xml_full_signature():
    # Instantiate the FullXmlSignatureStarter class, responsible for receiving the signature elements and start the
    # signature process
    try:
        signature_starter = FullXmlSignatureStarter(restpki_client)

        # Set the XML to be signed, a sample XML Document
        signature_starter.set_xml_path('%s/%s' % (STATIC_FOLDER, XML_DOCUMENT))

        # Set the location on which to insert the signature node. If the location is not specified, the signature will
        # appended to the root element (which is most usual with enveloped signatures).
        nsm = NamespaceManager()
        nsm.add_namespace('ls', 'http://www.lacunasoftware.com/sample')
        signature_starter.set_signature_element_location('//ls:signaturePlaceholder',
                                                         XmlInsertionOptions.appendChild, nsm)

        # Set the signature policy
        signature_starter.signature_policy_id = StandardSignaturePolicies.XADES_BES

        # Set a SecurityContext to be used to determine trust in the certificate chain
        signature_starter.security_context_id = StandardSecurityContexts.PKI_BRAZIL
        # Note: By changing the SecurityContext above you can accept only certificates from a certain PKI, for instance,
        # ICP-Brasil (lacunarestpki.StandardSecurityContexts.PKI_BRAZIL).

        # Call the start_with_webpki() method, which initiates the signature. This yields the token, a 43-character
        # case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
        # signWithRestPki() method on the Web PKI component (see lacuna-web-pki-client.js javascript) and also to
        # complete the signature after the form is submitted (see method pades_signature_action())). This should not be
        # mistaken with the API access token.
        token = signature_starter.start_with_webpki()

    except Exception as e:
        return render_template('error.html', msg=e)

    response = make_response(render_template('xml-full-signature.html', token=token))

    # The token acquired above can only be used for a single signature attempt. In order to retry the signature it is
    # necessary to get a new token. This can be a problem if the user uses the back button of the browser, since the
    # browser might show a cached page that we rendered previously, with a now stale token. # we force page expiration
    # through HTTP headers to prevent caching of the page.
    response.headers = get_expired_page_headers()

    return response


@blueprint.route('/xml-element-signature')
def xml_element_signature():
    # Instantiate the FullXmlSignatureStarter class, responsible for receiving the signature elements and start the
    # signature process
    try:
        signature_starter = XmlElementSignatureStarter(restpki_client)

        # Set the XML to be signed, a sample XML Document
        signature_starter.set_xml_path('%s/%s' % (STATIC_FOLDER, NFE_SAMPLE))

        # Set the ID of the element to be signed
        signature_starter.element_tosign_id = 'NFe35141214314050000662550010001084271182362300'

        # Set the signature policy
        signature_starter.signature_policy_id = StandardSignaturePolicies.NFE_PADRAO_NACIONAL
        # Optionally, set a SecurityContext to be used to determine trust in the certificate chain. Since we're using
        # the StandardSignaturePolicies.NFE_PADRAO_NACIONAL policy, the security context will default to PKI
        # Brazil (ICP-Brasil) signature_starter.security_context_id = StandardSecurityContexts.PKI_BRAZIL
        # Note: By changing the SecurityContext above you can accept only certificates from a certain PKI

        # Call the start_with_webpki() method, which initiates the signature. This yields the token, a 43-character
        # case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
        # signWithRestPki() method on the Web PKI component (see lacuna-web-pki-client.js javascript) and also to
        # complete the signature after the form is submitted (see method pades_signature_action())). This should not be
        # mistaken with the API access token.
        token = signature_starter.start_with_webpki()

    except Exception as e:
        return render_template('error.html', msg=e)

    response = make_response(render_template('xml-element-signature.html', token=token))

    # The token acquired above can only be used for a single signature attempt. In order to retry the signature it is
    # necessary to get a new token. This can be a problem if the user uses the back button of the browser, since the
    # browser might show a cached page that we rendered previously, with a now stale token. # we force page expiration
    # through HTTP headers to prevent caching of the page.
    response.headers = get_expired_page_headers()

    return response


@blueprint.route('/xml-signature-action', methods=['POST'])
def xml_signature_action():
    # Get the token for this signature (rendered in a hidden input field, see xml-signature.html template)
    token = request.form['token']

    # Instantiate the XmlSignatureFinisher class, responsible for completing the signature process
    signature_finisher = XmlSignatureFinisher(restpki_client)

    # Set the token
    signature_finisher.token = token

    # Call the finish() method, which finalizes the signature process and returns the signed XML
    signature_finisher.finish()

    # Get information about the certificate used by the user to sign the file. This method must only be called after
    # calling the finish() method.
    signer_cert = signature_finisher.certificate

    # At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
    # store the XML on a temporary folder publicly accessible and render a link to it.

    filename = '%s.xml' % (str(uuid.uuid1()))
    signature_finisher.write_signed_xml(os.path.join(app.config['APPDATA_FOLDER'], filename))

    return render_template('xml-signature-info.html', filename=filename, signer_cert=signer_cert)
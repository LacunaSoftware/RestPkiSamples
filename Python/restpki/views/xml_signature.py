import os
import uuid

from flask import make_response
from flask import render_template
from flask import request
from flask import current_app
from flask import Blueprint
from restpki_client.full_xml_signature_starter import FullXmlSignatureStarter
from restpki_client.namespace_manager import NamespaceManager
from restpki_client.xml_insertion_options import XmlInsertionOptions
from restpki_client.standard_signature_policies import StandardSignaturePolicies
from restpki_client.xml_element_signature_starter import \
    XmlElementSignatureStarter
from restpki_client.xml_signature_finisher import XmlSignatureFinisher

from restpki.utils import get_restpki_client
from restpki.utils import create_app_data
from restpki.utils import get_expired_page_headers
from restpki.utils import get_security_context_id
from restpki.utils import get_sample_nfe_path
from restpki.utils import get_sample_xml_document_path


blueprint = Blueprint('xml_signature', __name__, url_prefix='/xml-signature')


@blueprint.route('/full')
def full():
    """

    This function initiates a XML element signature using REST PKI and renders
    the signature page. The full XML signature is recommended in cases which
    there is a need to sign the whole XML file.

    """

    try:

        # Get an instance of the FullXmlSignatureStarter class, responsible for
        # receiving the signature elements and start the signature process.
        signature_starter = FullXmlSignatureStarter(get_restpki_client())

        # Set the XML to be signed, a sample XML Document.
        signature_starter.set_xml_path(get_sample_xml_document_path())

        # Set the location on which to insert the signature node. If the
        # location is not specified, the signature will appended to the root
        # element (which is most usual with enveloped signatures).
        nsm = NamespaceManager()
        nsm.add_namespace('ls', 'http://www.lacunasoftware.com/sample')
        signature_starter.set_signature_element_location(
            '//ls:signaturePlaceholder',
            XmlInsertionOptions.appendChild,
            nsm
        )

        # Set the signature policy.
        signature_starter.signature_policy_id = \
            StandardSignaturePolicies.XADES_BES

        # Set the security context to be used to determine trust in the
        # certificate chain. We have encapsulated the security context choice on
        # util.py.
        signature_starter.security_context_id = get_security_context_id()

        # Call the start_with_webpki() method, which initiates the signature.
        # This yields the token, a 43-character case-sensitive URL-safe string,
        # which identifies this signature process. We'll use this value to call
        # the signWithRestPki() method on the Web PKI component (see
        # signature-form.js javascript) and also to complete the signature after
        # the form is submitted (see method pades_signature_action()). This
        # should not be mistaken with the API access token.
        token = signature_starter.start_with_webpki()

        # The token acquired above can only be used for a single signature
        # attempt. In order to retry the signature it is necessary to get a new
        # token. This can be a problem if the user uses the back button of the
        # browser, since the browser might show a cached page that we rendered
        # previously, with a now stale token. To prevent this from happen, we
        # force page expiration through HTTP headers to prevent caching of the
        # page.
        response = make_response(render_template('xml_signature/full.html',
                                                 token=token))
        response.headers = get_expired_page_headers()
        return response

    except Exception as e:
        return render_template('error.html', msg=e)


@blueprint.route('/element')
def element():
    """

    This function initiates a XML element signature using REST PKI and renders
    the signature page. The XML element signature is recommended in cases which
    there is a need to sign a specific element of a XML.

    """

    # Instantiate the XmlElementSignatureStarter class, responsible for
    # receiving the signature elements and start the signature process.
    try:
        signature_starter = XmlElementSignatureStarter(get_restpki_client())

        # Set the XML to be signed, a sample XML Document.
        signature_starter.set_xml_path(get_sample_nfe_path())

        # Set the ID of the element to be signed.
        signature_starter.element_tosign_id = \
            'NFe35141214314050000662550010001084271182362300'

        # Set the signature policy.
        signature_starter.signature_policy_id = \
            StandardSignaturePolicies.NFE_PADRAO_NACIONAL

        # Set a security context to be used to determine trust in the
        # certificate chain. We have encapsulated the security context choice on
        # util.py.
        signature_starter.security_context_id = get_security_context_id()

        # Call the start_with_webpki() method, which initiates the signature.
        # This yields the token, a 43-character case-sensitive URL-safe string,
        # which identifies this signature process. We'll use this value to call
        # the signWithRestPki() method on the Web PKI component (see
        # signature-form.js javascript) and also to complete the signature after
        # the form is submitted (see method action()). This should not be
        # mistaken with the API access token.
        token = signature_starter.start_with_webpki()

        # The token acquired above can only be used for a single signature
        # attempt. In order to retry the signature it is necessary to get a new
        # token. This can be a problem if the user uses the back button of the
        # browser, since the browser might show a cached page that we rendered
        # previously, with a now stale token. We force page expiration through
        # HTTP headers to prevent caching of the page.
        response = make_response(render_template('xml_signature/element.html',
                                                 token=token))
        response.headers = get_expired_page_headers()
        return response

    except Exception as e:
        return render_template('error.html', msg=e)


@blueprint.route('/action', methods=['POST'])
def action():
    """

    This function receives the form submission from the template
    xml-signature/index.html. We'll call REST PKI to complete the signature.

    """

    # Get the token for this signature. (rendered in a hidden input field, see
    # xml-signature/index.html template)
    token = request.form['token']

    # Instantiate the XmlSignatureFinisher class, responsible for completing
    # the signature process.
    signature_finisher = XmlSignatureFinisher(get_restpki_client())

    # Set the token.
    signature_finisher.token = token

    # Call the finish() method, which finalizes the signature process and
    # returns the signed XML.
    signature_finisher.finish()

    # Get information about the certificate used by the user to sign the file.
    # This method must only be called after calling the finish() method.
    signer_cert = signature_finisher.certificate

    # At this point, you'd typically store the signed PDF on your database. For
    # demonstration purposes, we'll store the XML on a temporary folder publicly
    # accessible and render a link to it.

    create_app_data()  # Guarantees that "app data" folder exists.
    filename = '%s.xml' % (str(uuid.uuid1()))
    signature_finisher.write_signed_xml(
        os.path.join(current_app.config['APPDATA_FOLDER'], filename))

    return render_template('xml_signature/action.html',
                           filename=filename,
                           signer_cert=signer_cert)

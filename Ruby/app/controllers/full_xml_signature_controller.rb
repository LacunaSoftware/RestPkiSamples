class FullXmlSignatureController < ApplicationController


    # This action initiates a full XML signature using REST PKI and renders the signature page.
    def index
        begin

            # Instantiate the FullXmlSignatureStarter class, responsible for receiving the signature elements and start
            # the signature process (see config/initializers/restpki.rb)
            signature_starter = RestPki::FullXmlSignatureStarter.new(get_restpki_client)

            # Set path of the XML to be signed, a sample XML Document
            signature_starter.set_xml_tosign_from_path(get_sample_xml_path)

            # Set the location on which to insert the signature node. If the location is not specified, the signature
            # will appended to the root element (which is most usual with enveloped signatures).
            nsm = RestPki::NamespaceManager.new
            nsm.add_namespace('ls', 'http://www.lacunasoftware.com/sample')
            signature_starter.set_signature_element_location(
                '//ls:signaturePlaceholder',
                RestPki::XmlInsertionOptions::APPEND_CHILD,
                nsm
            )

            # Set the signature policy
            signature_starter.signature_policy_id = RestPki::StandardSignaturePolicies::XML_XADES_BES

            # Set the security context to be used to determine trust in the certificate chain
            signature_starter.security_context_id = get_security_context_id

            # Call the :start_with_webpki method, which initiates the signature. This yields the token, a 43-character
            # case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
            # sign_with_restpki method on the Web PKI component (see assets/js/signature-form.js) and also to complete
            # the signature after the form is submitted (see method action below). This should not be mistaken with the
            # API access token.
            @token = signature_starter.start_with_webpki

            # The token acquired above can only be used for a single signature attempt. In order to retry the signature
            # it is necessary to get a new token. This can be a problem if the user uses the back button of the browser,
            # since the browser might show a cached page that we rendered previously, with a now stale token. To prevent
            # this from happening, we call the method set_expired_page_headers, located in application_helper.rb,
            # which sets HTTP headers to prevent caching of the page.
            set_expired_page_headers

        rescue => ex
            @error = ex
            render 'layouts/_error'
        end
    end

    # This action receives the form submission from the signature page. We'll call REST PKI to complete the signature.
    def action
        begin

            # Get the token for this signature (rendered in a hidden input field, see full_xml_signature/new.html.erb)
            token = params[:token]

            # Instantiate the XmlSignatureFinisher class, responsible for completing the signature
            # process (see config/initializers/restpki.rb)
            signature_finisher = RestPki::XmlSignatureFinisher.new(get_restpki_client)

            # Set the token
            signature_finisher.token = token

            # Call the finish method, which finalizes the signature process and returns the signed XML's bytes
            signed_bytes = signature_finisher.finish

            # Get information about the certificate used by the user to sign the file. This field can only be acquired
            # after calling the finish method.
            @signer_cert = signature_finisher.certificate_info

            # At this point, you'd typically store the signed XML on your database. For demonstration purposes, we'll
            # store the XML on a temporary folder publicly accessible and render a link to it.

            @filename = SecureRandom.hex(10).to_s + '.xml'
            File.open(Rails.root.join('public', 'uploads', @filename), 'wb') do |file|
                file.write(signed_bytes)
            end

        rescue => ex
            @error = ex
            render 'layouts/_error'
        end
    end

end

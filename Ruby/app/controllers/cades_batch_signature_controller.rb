class CadesBatchSignatureController < ApplicationController
    include ApplicationHelper

    def index
        begin

            # It is up to your application's business logic to determine which element ids will compose the batch.
            @documents_ids = Array.new

            (1..30).each do |i|
                @documents_ids.push("%02d" % i)
            end


        rescue => ex

            @error = ex
            render 'layouts/_error'

        end
    end

    def start

        # Get the document id for this signature (received from the POST call, see cades-batch-signature-form.js)
        id = params[:id]

        # Instantiate the CadesSignatureStarter class, responsible for receiving the signature elements and start
        # the signature process
        signature_starter = RestPki::CadesSignatureStarter.new(get_restpki_client)

        # Set the document to be signed based on its ID
        signature_starter.set_file_tosign_from_path(get_sample_doc_path_from_id(id))

        # Set the signature policy
        signature_starter.signature_policy_id = RestPki::StandardSignaturePolicies::CADES_ICPBR_ADR_BASICA

        # Optionally, set a SecurityContext to be used to determine trust in the certificate chain
        # signature_starter.security_context_id = RestPki::StandardSecurityContexts::PKI_BRAZIL
        # Note: Depending on the signature policy chosen above, setting the security context may be mandatory (this
        # is not the case for ICP-Brasil policies, which will automatically use the PKI_BRAZIL security context if
        # none is passed)

        # Optionally, set whether the content should be encapsulated in the resulting CMS. If this parameter is
        # omitted, the following rules apply:
        #  - If no CmsToSign is given, the resulting CMS will include the content
        #  - If a CmsToCoSign is given, the resulting CMS will include the content if and only if the CmsToCoSign
        #    also includes the content
        signature_starter.encapsulate_content = true

        # Call the start_with_webpki method, which initiates the signature. This yields the token, a 43-character
        # case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
        # sign_with_restpki method on the Web PKI component (see signature-form.js) and also to complete the
        # signature after the form is submitted (see method create below). This should not be mistaken with the
        # API access token.
        token = signature_starter.start_with_webpki

        render :json => token.to_json

    end

    def complete

        # Get the token for this signature (received from the POST call, see cades-batch-signature-form.js)
        token = params[:token]

        # Instantiate the CadesSignatureFinisher class, responsible for completing the signature process
        signature_finisher = RestPki::CadesSignatureFinisher.new(get_restpki_client)

        # Set the token
        signature_finisher.token = token

        # Call the finish method, which finalizes the signature process and returns the signed CMS bytes
        signed_bytes = signature_finisher.finish

        # At this point, you'd typically store the signed CMS on your database. For demonstration purposes, we'll
        # store the CMS on a temporary folder publicly accessible and render a link to it.

        filename = SecureRandom.hex(10).to_s + '.p7s'
        signature_finisher.write_cms_to_path(Rails.root.join('public', 'uploads', filename))

        render :json => filename.to_json

    end
end

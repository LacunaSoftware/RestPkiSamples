class CadesBatchSignatureController < ApplicationController


    # This action renders the batch singature page.
    #
    # Notice that the only thing we'll do on the server-side at this point is determine the IDs of the documents
    # to be signed. The page will handle each document one by one and will call the server asynchronously to
    # start and complete each signature.
    def index
        begin

            # It is up to your application's business logic to determine which element ids will compose the batch.
            @documents_ids = Array.new

            # from 1 to 30
            (1..30).each do |i|
                @documents_ids.push('%02d' % i)
            end

        rescue => ex

            @error = ex
            render 'layouts/_error'

        end
    end

    # This action is called asynchronously from the batch signature page in order to initiate the signature of each
    # document in the batch.
    def start
        begin

            # Get the document id for this signature (received from the POST call, see batch-signature-form.js)
            id = params[:id]

            # Get an instance the CadesSignatureStarter class, responsible for receiving the signature elements and start
            # the signature process
            signature_starter = RestPki::CadesSignatureStarter.new(get_restpki_client)

            # Set the document to be signed based on its ID (passed to us from the page)
            signature_starter.set_file_tosign_from_path(get_batch_doc_path(id))

            # Set the signature policy
            signature_starter.signature_policy_id = RestPki::StandardSignaturePolicies::CADES_ICPBR_ADR_BASICA

            # Set the security context to be used to determine trust in the certificate chain
            signature_starter.security_context_id = get_security_context_id

            # Optionally, set whether the content should be encapsulated in the resulting CMS. If this parameter is
            # omitted, the following rules apply:
            #  - If no cms-to-cosign is given, the resulting CMS will include the content
            #  - If a cms-to-cosign is given, the resulting CMS will include the content if and only if the cms also
            #    includes the content
            signature_starter.encapsulate_content = true

            # Call the start_with_webpki method, which initiates the signature. This yields the token, a 43-character
            # case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
            # sign_with_restpki method on the Web PKI component (see batch-signature-form.js) and also to complete the
            # signature after the signature is computed by Web PKI (see method complete below). This should not be mistaken
            # with the API access token.
            token = signature_starter.start_with_webpki

            # Return a JSON with the token obtained from REST PKI (the page will use jQuery to decode this value)
            render :json => token.to_json

        rescue => e
            render :plain => e.message, :status => :internal_server_error
        end
    end

    # This action is called asynchronously from the batch signature page in order to complete the signature.
    #
    # Notice that the "id" is actually the signature process token. We're naming it "id" so that the action
    # can be called as /cades_batch_signature/complete/{token}
    def complete
        begin

            # Get the token for this signature (received from the POST call, see batch-signature-form.js)
            token = params[:id]

            # Instantiate the CadesSignatureFinisher class, responsible for completing the signature process
            signature_finisher = RestPki::CadesSignatureFinisher.new(get_restpki_client)

            # Set the token
            signature_finisher.token = token

            # Call the finish method, which finalizes the signature process and returns the signed CMS bytes
            signed_bytes = signature_finisher.finish

            # At this point, you'd typically store the signed CMS on your database. For demonstration purposes, we'll
            # store the CMS on a temporary folder publicly accessible and render a link to it.

            filename = SecureRandom.hex(10).to_s + '.p7s'
            File.open(Rails.root.join('public', 'uploads', filename), 'wb') do |file|
                file.write(signed_bytes)
            end

            # Return a JSON with the filename of the signed file (the page will use jQuery to decode this value)
            render :json => filename.to_json

        rescue => e
            render :plain => e.message, :status => :internal_server_error
        end
    end
end

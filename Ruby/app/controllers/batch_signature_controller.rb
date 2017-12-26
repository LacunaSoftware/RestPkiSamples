class BatchSignatureController < ApplicationController
    include PadesHelper

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

            # Get an instance the PadesSignatureStarter class, responsible for receiving the signature elements and start
            # the signature process
            signature_starter = RestPki::PadesSignatureStarter.new(get_restpki_client)

            # Set the document to be signed based on its ID (passed to us from the page)
            signature_starter.set_pdf_tosign_from_path(get_batch_doc_path(id))

            # Set the signature policy
            signature_starter.signature_policy_id = RestPki::StandardSignaturePolicies::PADES_BASIC

            # Set the security context to be used to determine trust in the certificate chain
            signature_starter.security_context_id = get_security_context_id

            # Set the visual representation for the signature
            signature_starter.visual_representation = {
                text: {

                    # The tags {{name}} and {{national_id}} will be substituted according to the user's certificate
                    #
                    # name        : full name of the signer
                    # national_id : if the certificate is ICP-Brasil, contains the signer's CPF
                    #
                    # For a full list of the supported tags, see: https://github.com/LacunaSoftware/RestPkiSamples/blob/master/PadesTags.md
                    text: 'Signed by {{signerName}} ({{signerNationalId}})',
                    # Specify that the signing time should also be rendered
                    horizontalAlign: 'Left',
                    # Optionally set the horizontal alignment of the text ('Left' or 'Right'), if not set the default is
                    # Left
                    includeSigningTime: true,
                    # Optionally set the container within the signature rectangle on which to place the text. By
                    # default, the text can occupy the entire rectangle (how much of the rectangle the text will
                    # actually fill depends on the length and font size). Below, we specify that the text should respect
                    # a right margin of 1.5 cm.
                    container: {
                        left: 0,
                        top: 0,
                        right: 1.5,
                        bottom: 0
                    }

                },
                image: {

                    # We'll use as background the image content/PdfStamp.png
                    resource: {
                        content: Base64.encode64(get_pdf_stamp_content),
                        mimeType: 'image/png'
                    },
                    # Opacity is an integer from 0 to 100 (0 is completely transparent, 100 is completely opaque).
                    opacity: 50,
                    # Align the image to the right
                    horizontalAlign: 'Right',
                    # Align the image to the center
                    verticalAlign: 'Center'

                },
                # Position of the visual representation. We have encapsulated this code in a function to include several
                # possibilities depending on the argument passed to the function. Experiment changing the argument to
                # see different examples of signature positioning. Once you decide which is best for your case, you can
                # place the code directly here. See file helpers/pades_helper.rb
                position: get_visual_representation_position(1)
            }

            # Call the start_with_webpki method, which initiates the signature. This yields the token, a 43-character
            # case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
            # sign_with_restpki method on the Web PKI component (see cades-batch-signature-form.js) and also to complete the
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
    # can be called as /batch_signature/complete/{token}
    def complete
        begin

            # Get the token for this signature (received from the POST call, see batch-signature-form.js)
            token = params[:id]

            # Instantiate the PadesSignatureFinisher class, responsible for completing the signature process
            signature_finisher = RestPki::PadesSignatureFinisher.new(get_restpki_client)

            # Set the token
            signature_finisher.token = token

            # Call the finish method, which finalizes the signature process and returns the signed CMS bytes
            signed_bytes = signature_finisher.finish

            # At this point, you'd typically store the signed CMS on your database. For demonstration purposes, we'll
            # store the CMS on a temporary folder publicly accessible and render a link to it.

            filename = SecureRandom.hex(10).to_s + '.pdf'
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

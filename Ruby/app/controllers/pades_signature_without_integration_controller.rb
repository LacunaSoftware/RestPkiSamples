class PadesSignatureWithoutIntegrationController < ApplicationController
    include PadesHelper


    # This action simple renders the page
    def index

        # Set the state as "initial" to be passed to javascript to perform the relative step.
        @state = 'initial'

        @userfile = params[:userfile]
        if not @userfile.nil? and not @userfile.to_s.empty?

            # If the URL argument "userfile" is filled, it means the user was redirected by upload_controller
            # (signature with file upload by user). We'll set the path of the file to be signed, which was saved in
            # the temporary folder by upload_controller (such a file would normally come from your application's
            # database)
            unless File.exist? Rails.root.join('public', 'uploads', @userfile)
                raise 'File not found!'
            end
            @file_to_sign = Rails.root.join('public', 'uploads', @userfile)

        else

            # If userfile is null, this is the "signature with server file" case. We'll set the file to be signed
            # by passing its path
            @file_to_sign = get_sample_doc_path
        end

    rescue => ex
        @error = ex
        render 'layouts/_error'
    end

    # This action receives the form submission from the signature page. It will perform a PAdES signature without
    # needing the integration between REST PKI and Web PKI.
    def action

        @state = params[:state]
        if @state.eql? 'start'

            # This block will be executed only when it's on the "start" step. In this sample, the state is set as "start"
            # programativally after the user press the "Sign File" button (see method sign() on
            # signature-without-integration-form.js).
            begin

                # Recover variables from the POST arguments to be used on this step.
                @cert_content = params[:cert_content]
                @file_to_sign = params[:file_to_sign]

                # Get an instance of the PadesSignatureStarter class, responsible for receiving the signature elements
                # and start the signature process.
                signature_starter = RestPki::PadesSignatureStarter.new(get_restpki_client)

                # Set PDF to be signed.
                signature_starter.set_pdf_tosign_from_path(@file_to_sign)

                # Set Base64-encoded certificate's content to signature starter.
                signature_starter.signer_certificate_base64 = @cert_content

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

                # Call the start method, which initiates the signature. This yields the parameters for the signature
                # using the certificate.
                signature_params = signature_starter.start

                # Render the fields received form start method as hidden fields to be used on the javascript or on the
                # "complete" step.
                @to_sign_hash = signature_params[:to_sign_hash]
                @digest_algorithm_oid = signature_params[:digest_algorithm_oid]
                @token = signature_params[:token]
                @cert_thumb = params[:cert_thumb]

            rescue => ex

                # Return to "initial" state rendering the error message.
                @error_message = ex.message
                @error_title = 'Signature Initialization Failed'
                @state = 'initial'
                @file_to_sign = params[:file_to_sign]

            end

        elsif @state.eql? 'complete'

            # This block will be executed only when it's on the "complete" step. In this sample, the state is set as
            # "complete" programatically after the Web PKI component perform the signature and submit the form (see
            # method sign() on signature-without-integration-form.js).
            begin

                # Recover variables from the POST arguments to be used on this step.
                token = params[:token]
                signature = params[:signature]

                # Instantiate the PadesSignatureFinisher class, responsible for completing the signature process
                # (see config/initializers/restpki.rb)
                signature_finisher = RestPki::PadesSignatureFinisher.new(get_restpki_client)

                # Set the token
                signature_finisher.token = token

                # Set the signature
                signature_finisher.signature = signature

                # Call the finish method, which finalizes the signature process and returns the signed PDF's bytes
                signed_bytes = signature_finisher.finish

                # At this point, you'd typically store the signed XML on your database. For demonstration purposes,
                # we'll store the XML on a temporary folder publicly accessible and render a link to it.

                @filename = SecureRandom.hex(10).to_s + '.pdf'
                File.open(Rails.root.join('public', 'uploads', @filename), 'wb') do |file|
                    file.write(signed_bytes)
                end

                @state = 'completed'

            rescue => ex

                # Return to "initial" state rendering the error message.
                @error_message = ex.message
                @error_title = 'Signature Finalization Failed'
                @state = 'initial'
                @file_to_sign = params[:file_to_sign]

            end
        end

        # Render the signature page again.
        render 'pades_signature_without_integration/index'
    end
end

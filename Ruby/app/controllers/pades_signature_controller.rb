class PadesSignatureController < ApplicationController
    include ApplicationHelper, PadesHelper
    before_action :set_expired_page_headers
    # The token acquired below can only be used for a single authentication attempt. In order to retry the signature it
    # is necessary to get a new token. This can be a problem if the user uses the back button of the browser, since the
    # browser might show a cached page that we rendered previously, with a now stale token. To prevent this from
    # happening, we call the method :set_expired_page_headers, located in application_controller.rb, which sets HTTP
    # headers to prevent caching of the page.

    # This action initiates a PAdES signature using REST PKI and renders the signature page.
    #
    # Both PAdES signature examples, with a server file and with a file uploaded by the user, converge to this action.
    # The difference is that, when the file is uploaded by the user, the action is called with a URL argument name
    # "userfile".
    def index
        begin

            # Instantiate the PadesSignatureStarter class, responsible for receiving the signature elements and start
            # the signature process
            signature_starter = RestPki::PadesSignatureStarter.new(get_restpki_client)

            # Set the signature policy
            signature_starter.signature_policy_id = RestPki::StandardSignaturePolicies::PADES_BASIC_WITH_ICPBR_CERTS
            # Note: Depending on the signature policy chosen above, setting the security context below may be mandatory
            # (this is not the case for ICP-Brasil policies, which will automatically use the PkiBrazil security context
            # if none is passed)

            # Alternative option: add a ICP-Brasil timestamp to the signature.
            # signature_starter.signature_policy_id = RestPki::StandardSignaturePolicies::PADES_T_WITH_ICPBR_CERTS

            # Alternative option: PAdES Basic with PKIs trusted by Windows.
            # signature_starter.signature_policy_id = RestPki::StandardSignaturePolicies::PADES_BASIC
            # signature_starter.security_context_id = RestPki::StandardSecurityContexts::WINDOWS_SERVER

            # Alternative option: PAdES Basic with a custom security context containing, for instance, your private PKI
            # certificate
            # signature_starter.signature_policy_id = RestPki::StandardSignaturePolicies::PADES_BASIC
            # signature_starter.security_context_id = 'ID OF YOUR CUSTOM SECURITY CONTEXT'

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

            # Below we'll either set the PDF file to be signed. Prefer passing a path or a stream instead of the file's
            # contents as a byte array to prevent memory allocation issues with large files.
            @userfile = params[:userfile]
            if @userfile.nil?

                # If the URL argument "userfile" is filled, it means the user was redirected by upload_controller
                # (signature with file upload by user). We'll set the path of the file to be signed, which was saved in
                # the temporary folder by upload_controller (such a file would normally come from your application's
                # database)
                signature_starter.set_pdf_tosign_from_path(get_sample_doc_path)

            else

                # If userfile is null, this is the "signature with server file" case. We'll set the file to be signed
                # by passing its path
                signature_starter.set_pdf_tosign_from_path(Rails.root.join('public', 'uploads', @userfile))

            end

            # Call the start_with_webpki method, which initiates the signature. This yields the token, a 43-character
            # case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
            # sign_with_restpki method on the Web PKI component (see signature-form.js) and also to complete the
            # signature after the form is submitted (see method create below). This should not be mistaken with the
            # API access token.
            @token = signature_starter.start_with_webpki

        rescue => ex
            @error = ex
            render 'layouts/_error'
        end
    end

    # This action receives the form submission from the signature page. We'll call REST PKI to complete the signature.
    def action
        begin

            # Get the token for this signature (rendered in a hidden input field, see pades_signature/new.html.erb)
            token = params[:token]

            # Instantiate the PadesSignatureFinisher class, responsible for completing the signature process
            # (see config/initializers/restpki.rb)
            signature_finisher = RestPki::PadesSignatureFinisher.new(get_restpki_client)

            # Set the token
            signature_finisher.token = token

            # Call the finish method, which finalizes the signature process and returns the signed PDF's bytes
            signed_bytes = signature_finisher.finish

            # Get information about the certificate used by the user to sign the file. This field can only be acquired
            # after calling the finish method.
            @signer_cert = signature_finisher.certificate_info

            # At this point, you'd typically store the signed XML on your database. For demonstration purposes, we'll
            # store the XML on a temporary folder publicly accessible and render a link to it.

            @filename = SecureRandom.hex(10).to_s + '.pdf'
            File.open(Rails.root.join('public', 'uploads', @filename), 'wb') do |file|
                file.write(signed_bytes)
            end

        rescue => ex
            @error = ex
            render 'layouts/_error'
        end
    end
end

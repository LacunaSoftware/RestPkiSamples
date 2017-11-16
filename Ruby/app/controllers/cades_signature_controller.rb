class CadesSignatureController < ApplicationController


    # This action initiates a CAdES signature using REST PKI and renders the signature page.
    #
    # All CAdES signature examples converge to this action, but with different URL arguments:
    #
    # 1. Signature with a server file               : no arguments filled
    # 2. Signature with a file uploaded by the user : "userfile" filled
    # 3. Co-signature of a previously signed CMS    : "cmsfile" filled
    def index
        begin

            @userfile = params[:userfile]
            @cmsfile = params[:cmsfile]

            # Get an instance the CadesSignatureStarter class, responsible for receiving the signature elements and start
            # the signature process
            signature_starter = RestPki::CadesSignatureStarter.new(get_restpki_client)

            if not @userfile.nil?

                # If the URL argument "userfile" is filled, it means the user was redirected by upload_controller
                # (signature with file upload by user). We'll set the path of the file to be signed, which was saved in
                # the temporary folder by upload_controller (such a file would normally come from your application's
                # database)
                signature_starter.set_file_tosign_from_path(Rails.root.join('public', 'uploads', @userfile))

            elsif not @cmsfile.nil?

                 # If the URL argument "cmsfile" is filled, the user has asked to co-sign a previously signed CMS. We'll
                 # set the path to the CMS to be co-signed, which was previously saved in the public/uploads/ folder by
                 # the file action method. Note two important things:
                 #
                 # 1. The CMS to be co-signed must be set using one of the methods "set_cms_tocosign", not one of the
                 #    methods "set_file_tosign".
                 #
                 # 2. Since we're creating CMSs with encapsulated content (see call to encapsulate_content below), we
                 #    don't need to set the content to be signed, REST PKI will get the content from the CMS being
                 #    co-signed.
                signature_starter.set_cms_tocosign_from_path(Rails.root.join('public', 'uploads', @cmsfile))

            else

                # If userfile is null, this is the "signature with server file" case. We'll set the file to be signed
                # by passing its path
                signature_starter.set_file_tosign_from_path(get_sample_doc_path)

            end

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
            # sign_with_restpki method on the Web PKI component (see signature-form.js) and also to complete the
            # signature after the form is submitted (see method action below). This should not be mistaken with the
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

    # This action receives the form submission from the view. We'll call REST PKI to complete the signature.
    def action
        begin

            # Get the token for this signature (rendered in a hidden input field, see cades_signature/index.html.erb)
            token = params[:token]

            # Instantiate the CadesSignatureFinisher class, responsible for completing the signature process
            signature_finisher = RestPki::CadesSignatureFinisher.new(get_restpki_client)

            # Set the token
            signature_finisher.token = token

            # Call the finish method, which finalizes the signature process and returns the signed CMS bytes
            signed_bytes = signature_finisher.finish

            # Get information about the certificate used by the user to sign the file. This field can only be acquired
            # after calling the finish method.
            @signer_cert = signature_finisher.certificate_info

            # At this point, you'd typically store the signed CMS on your database. For demonstration purposes, we'll
            # store the CMS on a temporary folder publicly accessible and render a link to it.

            @filename = SecureRandom.hex(10).to_s + '.p7s'
            File.open(Rails.root.join('public', 'uploads', @filename), 'wb') do |file|
                file.write(signed_bytes)
            end

        rescue => ex
            @error = ex
            render 'layouts/_error'
        end
    end
end

class AuthenticationController < ApplicationController


    # This action initiates an authentication with REST PKI and renders the authentication page.
    def index
        begin

            # Get an instance of the Authentication class
            auth = RestPki::Authentication.new(get_restpki_client)

            # Call the startWithWebPki method, which initiates the authentication. This yields the "token", a
            # 22-character case-sensitive URL-safe string, which represents this authentication process. We'll use this
            # value to call the signWithRestPki method on the Web PKI component (see assets/js/signature-form.js) and
            # also to call the completeWithWebPki method on the post action below. This should not be mistaken with the
            # API access token.
            @token = auth.start_with_webpki(get_security_context_id)

            # The token acquired above can only be used for a single authentication attempt. In order to retry the
            # signature it is necessary to get a new token. This can be a problem if the user uses the back button of
            # the browser, since the browser might show a cached page that we rendered previously, with a now stale
            # token. To prevent this from happening, we call the method set_expired_page_headers, located in
            # application_helper.rb, which sets HTTP headers to prevent caching of the page.
            set_expired_page_headers

        rescue => ex
            @error = ex
            render 'layouts/_error'
        end
    end

    # This action receives the form submission from the view. We'll call REST PKI to validate the authentication.
    def action
        begin

            # Get the token for this signature (rendered in a hidden input field, see authentication/index.html.erb)
            token = params[:token]

            # Get an instance of the Authentication class
            auth = RestPki::Authentication.new(get_restpki_client)

            # Call the completeWithWebPki method with the token, which finalizes the authentication process. The call
            # yields a ValidationResults which denotes whether the authentication was successful or not (we'll use it to
            # render the page accordingly, see authentication/action.html.erb).
            @vr = auth.complete_with_webpki(token)

            if @vr.is_valid
                @user_cert = auth.certificate_info
                # At this point, you have assurance that the certificate is valid according to the SecurityContext
                # specified on the index method and that the user is indeed the certificate's subject. Now, you'd
                # typically query your database for a user that matches one of the certificate's fields, such as
                # @user_cert.emailAddress or @user_cert.pkiBrazil.cpf (the actual field to be used as key depends on
                # your application's business logic) and set the user as authenticated with whatever web security
                # framework your application uses. For demonstration purposes, we'll just render the user's certificate
                # information.
            end

        rescue => ex
            @error = ex
            render 'layouts/_error'
        end
    end

end

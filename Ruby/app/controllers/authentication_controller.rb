class AuthenticationController < ApplicationController
    # The token acquired below can only be used for a single authentication attempt. In order to retry the signature it
    # is necessary to get a new token. This can be a problem if the user uses the back button of the browser, since the
    # browser might show a cached page that we rendered previously, with a now stale token. To prevent this from
    # happening, we call the method :set_expired_page_headers, located in application_controller.rb, which sets HTTP
    # headers to prevent caching of the page.
    before_action :set_expired_page_headers

    # This action initiates an authentication with REST PKI and renders the authentication page.
    def index
        begin

            # Get an instance of the Authentication class (see config/initializers/restpki.rb)
            auth = get_restpki_client.get_authentication

            # Call the startWithWebPki method, which initiates the authentication. This yields the "token", a
            # 22-character case-sensitive URL-safe string, which represents this authentication process. We'll use this
            # value to call the signWithRestPki method on the Web PKI component (see assets/js/signature-form.js) and
            # also to call the completeWithWebPki method on the post action below. This should not be mistaken with the
            # API access token.
            @token = auth.start_with_webpki(RestPki::StandardSecurityContexts::PKI_BRAZIL)
            # Note: By changing the SecurityContext above you can accept only certificates from a certain PKI, for
            # instance, ICP-Brasil (RestPki::StandardSecurityContexts::PKI_BRAZIL).

            # Alternative option: authenticate the user with a custom security context containing, for instance, your
            # private PKI certificate
            # @token = auth.start_with_webpki('ID OF YOUR CUSTOM SECURITY CONTEXT')

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

            # Get an instance of the Authentication class (see config/initializers/restpki.rb)
            auth = get_restpki_client.get_authentication

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

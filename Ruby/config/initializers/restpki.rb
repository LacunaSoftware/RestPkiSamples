require 'rest_pki'

def get_restpki_client

    # ==================================================================================================================
    #                  >>>> PASTE YOUR API ACCESS TOKEN BELOW <<<<
    restpki_access_token = 'PLACE YOUR API ACCESS TOKEN HERE'
    #                       ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
    # ==================================================================================================================

    # Throw exception if token is not set (this check is here just for the sake of new comers, you can remove it)
    if restpki_access_token.include? ' API '
        raise 'The API access token was not set! Hint: to run this sample you must generate an API access token on the REST PKI website and paste it on the file config/initializers/restpki.rb'
    end

    # ------------------------------------------------------------------------------------------------------------------
    # IMPORTANT NOTICE: in production code, you should use HTTPS to communicate with REST PKI, otherwise your API
    # access token, as well as the documents you sign, will be sent to REST PKI unencrypted.
    # ------------------------------------------------------------------------------------------------------------------
    restpki_url = 'http://pki.rest/'
    # restpki_url = 'https://pki.rest/' # <--- USE THIS IN PRODUCTION!

    RestPki::RestPkiClient.new(restpki_url, restpki_access_token)
end

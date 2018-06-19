import os
from datetime import datetime, timedelta
from lacunarestpki import RestPkiClient, StandardSecurityContexts
from flask import current_app


def get_restpki_client():
    # ==========================================================================
    #                    >>> PASTE YOUR ACCESS TOKEN BELOW <<<
    # ==========================================================================
    restpki_access_token = 'YOUR API ACCESS TOKEN HERE'

    # Throw exception if token is not set (this check is here just for the sake
    # of newcomers, you can remove it)
    if ' API ' in restpki_access_token:
        raise Exception(
            'The API access token was not set! Hint: to run this sample you'
            'must generate an API access token on the REST PKI website and'
            'paste it on the file app/util.py'
        )

    restpki_url = 'https://pki.rest/'
    return RestPkiClient(restpki_url, restpki_access_token)


def get_security_context_id():
    """

    This method is called by all pages to determine the security context to
    be used.

    Security contexts dictate which root certification authorities are
    trusted during certificate validation. In your API calls, you can see
    one of the standard security contexts or reference one of your custom
    contexts.

    :return: StandardSecurityContexts

    """
    if current_app.env == 'development':
        # Lacuna Text PKI (for development purposes only!)
        #
        # This security context trusts ICP-Brasil certificates as well as
        # certificates on Lacuna Software's test PKI. Use it to accept the test
        # certificates provided by Lacuna Software.
        #
        # THIS SHOULD NEVER BE USED ON A PRODUCTION ENVIRONMENT!
        return StandardSecurityContexts.LACUNA_TEST
        # Notice for On Premises users: This security context might not exist on
        # your installation, if you encounter an error please contact developer
        # support.

    else:
        # In production, accepting only certificates from ICP-Brasil
        return StandardSecurityContexts.PKI_BRAZIL


def get_expired_page_headers():
    headers = dict()
    now = datetime.utcnow()
    expires = now - timedelta(seconds=3600)

    headers['Expires'] = expires.strftime("%a, %d %b %Y %H:%M:%S GMT")
    headers['Last-Modified'] = now.strftime("%a, %d %b %Y %H:%M:%S GMT")
    headers['Cache-Control'] = 'private, no-store, max-age=0, no-cache,' \
                               ' must-revalidate, post-check=0, pre-check=0'
    headers['Pragma'] = 'no-cache'
    return headers


def create_app_data():
    if not os.path.exists(current_app.config['APPDATA_FOLDER']):
        os.makedirs(current_app.config['APPDATA_FOLDER'])


def get_pdf_stamp_content():
    # Read the PDF stamp image
    f = open('%s/%s' % (current_app.static_folder, 'PdfStamp.png'), 'rb')
    pdf_stamp = f.read()
    f.close()
    return pdf_stamp

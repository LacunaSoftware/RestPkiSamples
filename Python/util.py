from datetime import datetime, timedelta

from lacunarestpki import RestPkiClient

# ----------------------------------------------------------------------------------------------------------------------
# PASTE YOUR ACCESS TOKEN BELOW
restpki_access_token = 'PLACE YOUR API ACCESS TOKEN HERE'
#                       ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
# ----------------------------------------------------------------------------------------------------------------------

# Throw exception if token is not set (this check is here just for the sake of newcomers, you can remove it)
if ' API ' in restpki_access_token:
    raise Exception(
        'The API access token was not set! Hint: to run this sample you must generate an API access '
        'token on the REST PKI website and paste it on the file util.py')

restpki_url = 'https://pki.rest/'
restpki_client = RestPkiClient(restpki_url, restpki_access_token)

# ----------------------------------------------------------------------------------------------------------------------
APPDATA_FOLDER = 'app_data'
STATIC_FOLDER = 'static'
TEMPLATE_DOCUMENT = 'SampleDocument.pdf'
XML_DOCUMENT = 'SampleDocument.xml'
NFE_SAMPLE = 'SampleNFe.xml'


# ----------------------------------------------------------------------------------------------------------------------
def get_expired_page_headers():
    headers = dict()
    now = datetime.utcnow()
    expires = now - timedelta(seconds=3600)

    headers['Expires'] = expires.strftime("%a, %d %b %Y %H:%M:%S GMT")
    headers['Last-Modified'] = now.strftime("%a, %d %b %Y %H:%M:%S GMT")
    headers['Cache-Control'] = 'private, no-store, max-age=0, no-cache, must-revalidate, post-check=0, pre-check=0'
    headers['Pragma'] = 'no-cache'
    return headers

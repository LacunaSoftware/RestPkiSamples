// ------------------------------------------------------------------------------------------------
// PLACE YOUR API ACCESS TOKEN BELOW
var accessToken = 'shi-CRLdC63300f9AGK2Qy6moO0JE7lXbNFNUDCqytplK2eN82uVcDsEPLr282eU-fxBEcVBF-lk77h5lrVosft0t-4vRnv0F2vcTexDKGxierLMPF4aeDLLntLJeSsdxtQRU7AdBwHLH3vgTA0CHFsNOZl6FDxx4y7_bqHm2SjhD5sLJE1riHDJy0QJUJ34RMxDHNo0GbRn7WFvUgvWfOLG_pJXGvf20wyFucZdDy282Vrqqfki0tnyBZbaaHUfAiJXsRdw8S-dx9Ooi5TnoxnUh08yQUMgXYVwwRYLCsv7mTCKK7fKuvD7xz0KkidqzAX8juL_a80tUFatVIcsIAkkbJteqULGquF1LXBFAk4aIjKy3HtExuXDSTLSEx1EY-n8wnI_P9ci-Lgrn_4TDP4MifADhJpSIDGyChA8zs6euDiOyjtzXymt8PdjgCslOMdwWUXe1HFAkfXgqJkp1KM5XIpYHbZrwNLTKx4AvJvP3Sr9dRfYciJq-93U_TP8HHLvhw';
//                 ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
// ------------------------------------------------------------------------------------------------

var restPkiUrl = 'https://pki.rest/';

// Throw exception if token is not set (this check is here just for the sake of newcomers, you can remove it)
if (!accessToken || accessToken.indexOf(' API ') >= 0) {
    throw 'The API access token was not set! Hint: to run this sample you must generate an API access token on the REST PKI website and paste it on the file routes/pades-signature.js';
}

module.exports = {
    accessToken: accessToken,
    endpoint: restPkiUrl
};
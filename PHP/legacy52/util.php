<?php

require_once 'RestPkiLegacy52.php';

require_once 'config.php';

function getRestPkiClient()
{

    $config = getConfig();
    $accessToken = $config['restPki']['accessToken'];

    // Throw exception if token is not set (this check is here just for the sake of newcomers, you can remove it).
    if (empty($accessToken) || strpos($accessToken, ' API ') !== false) {
        throw new Exception('The API access token was not set! Hint: to run this sample you must generate an API access token on the REST PKI website and paste it on the file config.php');
    }

    // -----------------------------------------------------------------------------------------------------------
    // IMPORTANT NOTICE: in production code, you should use HTTPS to communicate with REST PKI, otherwise your API
    // access token, as well as the documents you sign, will be sent to REST PKI unencrypted.
    // -----------------------------------------------------------------------------------------------------------
    $endpoint = $config['restPki']['endpoint'];
    if (empty($endpoint)) {
        $endpoint = 'http://pki.rest/';
        //$endpoint = 'https://pki.rest/'; // <--- USE THIS IN PRODUCTION!
    }

    return new RestPkiClient($endpoint, $accessToken);
}

/**
 * This method is called by all pages to determine the security context to be used.
 *
 * Security contexts dictate witch root certification authorities are trusted during
 * certificates validation. In your API calls, you can use one of the standard security
 * contexts or reference one of your custom contexts.
 */
function getSecurityContextId()
{

    /*
     * Lacuna Text PKI (for development purposes only!)
     *
     * This security context trusts ICP-Brasil certificates as well as certificates on Lacuna Software's test PKI.
     * Use it to accept the tes certificates provided by Lacuna Software, uncomment the following line.
     *
     * THIS SHOULD NEVER BE USED ON A PRODUCTION ENVIRONEMNT!
     * For more information, see https://github.com/LacunaSoftware/RestPkiSamples/blob/master/TestCertificates.md
     */
    //return RestPkiStandardSecurityContexts::LACUNA_TEST;

    // In production, accept only certificates from ICP-Brasil.
    return RestPkiStandardSecurityContexts::PKI_BRAZIL;
}

function setNoCacheHeaders()
{
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 3600) . ' GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: private, no-store, max-age=0, no-cache, must-revalidate, post-check=0, pre-check=0');
    header('Pragma: no-cache');
}

function createAppData()
{
    $appDataPath = "app-data";
    if (!file_exists($appDataPath)) {
        mkdir($appDataPath);
    }
}

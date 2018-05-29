<?php

use Lacuna\RestPki\RestPkiClient;
use Lacuna\RestPki\StandardSecurityContexts;

require __DIR__ . '/vendor/autoload.php';

function getRestPkiClient()
{

    $accessToken = getConfig()['restPki']['accessToken'];

    // Throw exception if token is not set (this check is here just for the sake of newcomers, you can remove it).
    if (empty($accessToken) || strpos($accessToken, ' API ') !== false) {
        throw new \Exception('The API access token was not set! Hint: to run this sample you must generate an API access token on the REST PKI website and paste it on the file config.php');
    }

    // -----------------------------------------------------------------------------------------------------------
    // IMPORTANT NOTICE: in production code, you should use HTTPS to communicate with REST PKI, otherwise your API
    // access token, as well as the documents you sign, will be sent to REST PKI unencrypted.
    // -----------------------------------------------------------------------------------------------------------
    $endpoint = getConfig()['restPki']['endpoint'];
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
 * certificate validation. In you API calls, you can use one of the standard security
 * contexts or reference one of you custom contexts.
 */
function getSecurityContextId() {

    /*
     * Lacuna Text PKI (for development purposes only!)
     *
     * This security context trusts ICP-Brasil certificates as well as certificates on Lacuna Software's
     * test PKI. Use it to accept the test certificates provided by Lacuna Software, uncomment the following
     * line.
     *
     * THIS SHOULD NEVER BE USED ON A PRODUCTION ENVIRONMENT!
     * For more information, see https://github.com/LacunaSoftware/RestPkiSamples/blob/master/TestCertificates.md
     */
    //return StandardSecurityContexts::LACUNA_TEST;

    // In production, accept only certificates from ICP-Brasil.
    return StandardSecurityContexts::PKI_BRAZIL;
}

function setExpiredPage()
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

function getPdfStampContent()
{
    return file_get_contents('content/PdfStamp.png');
}

function getIcpBrasilLogoContent()
{
    return file_get_contents('content/icp-brasil.png');
}

function getValidationResultIcon($isValid)
{
    $filename = $isValid ? 'ok.png' : 'not-ok.png';
    return file_get_contents('content/' . $filename);
}

function joinStringsPt($strings)
{
    $text = '';
    $count = count($strings);
    $index = 0;
    foreach ($strings as $s) {
        if ($index > 0) {
            if ($index < $count - 1) {
                $text .= ', ';
            } else {
                $text .= ' e ';
            }
        }
        $text .= $s;
        ++$index;
    }
    return $text;
}

function generateVerificationCode()
{
    /*
     * Configuration of the code generation
     * ------------------------------------
     *
     * - CodeSize   : size of the code in characters.
     *
     * Entropy
     * -------
     *
     * The resulting entropy of the code in bits is the size of the code times 4. Here are some suggestions:
     *
     * - 12 characters = 48 bits
     * - 16 characters = 64 bits
     * - 20 characters = 80 bits
     * - 24 characters = 92 bits
     */
    $codeSize = 16;

    // Generate the entropy with PHP's pseudo-random bytes generator function.
    $numBytes = floor($codeSize / 2);
    $randInt = openssl_random_pseudo_bytes($numBytes);

    return strtoupper(bin2hex($randInt));
}

function formatVerificationCode($code)
{
    /*
     * Examples
     * --------
     *
     * - CodeSize = 12, CodeGroups = 3 : XXXX-XXXX-XXXX
     * - CodeSize = 12, CodeGroups = 4 : XXX-XXX-XXX-XXX
     * - CodeSize = 16, CodeGroups = 4 : XXXX-XXXX-XXXX-XXXX
     * - CodeSize = 20, CodeGroups = 4 : XXXXX-XXXXX-XXXXX-XXXXX
     * - CodeSize = 20, CodeGroups = 5 : XXXX-XXXX-XXXX-XXXX-XXXX
     * - CodeSize = 25, CodeGroups = 5 : XXXXX-XXXXX-XXXXX-XXXXX-XXXXX
     */
    $codeGroups = 4;

    // Return the code separated in groups.
    $charsPerGroup = (strlen($code) - (strlen($code) % $codeGroups)) / $codeGroups;
    $text = '';
    for ($ind = 0; $ind < strlen($code); $ind++) {
        if ($ind != 0 && $ind % $charsPerGroup == 0) {
            $text .= '-';
        }
        $text .= $code[$ind];
    }

    return $text;
}

function parseVerificationCode($code)
{
    $text = '';
    for ($ind = 0; $ind < strlen($code); $ind++) {
        if ($code[$ind] != '-') {
            $text .= $code[$ind];
        }
    }

    return $text;
}

<?php

use Lacuna\RestPki\RestPkiClient;

require __DIR__ . '/vendor/autoload.php';

function getRestPkiClient()
{

    // -----------------------------------------------------------------------------------------------------------
    // PASTE YOUR ACCESS TOKEN BELOW
    $restPkiAccessToken = 'PLACE YOUR API ACCESS TOKEN HERE';
    //                     ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
    // -----------------------------------------------------------------------------------------------------------

    // Throw exception if token is not set (this check is here just for the sake of newcomers, you can remove it)
    if (strpos($restPkiAccessToken, ' API ') !== false) {
        throw new \Exception('The API access token was not set! Hint: to run this sample you must generate an API access token on the REST PKI website and paste it on the file api/util.php');
    }

    // -----------------------------------------------------------------------------------------------------------
    // IMPORTANT NOTICE: in production code, you should use HTTPS to communicate with REST PKI, otherwise your API
    // access token, as well as the documents you sign, will be sent to REST PKI unencrypted.
    // -----------------------------------------------------------------------------------------------------------
    $restPkiUrl = 'http://pki.rest/';
    //$restPkiUrl = 'https://pki.rest/'; // <--- USE THIS IN PRODUCTION!

    return new RestPkiClient($restPkiUrl, $restPkiAccessToken);
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
function getPdfLargeStampContent()
{
    return file_get_contents('content/LargeStamp.png');
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
     * - CodeSize   : size of the code in characters
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

    // Generate the entropy with PHP's pseudo-random bytes generator function
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

    // Return the code separated in groups
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

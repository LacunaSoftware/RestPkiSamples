<?php

require_once 'RestPkiLegacy52.php';

function getRestPkiClient() {

	// -----------------------------------------------------------------------------------------------------------
	// PASTE YOUR ACCESS TOKEN BELOW
    $restPkiAccessToken = 'PLACE YOUR API ACCESS TOKEN HERE';
    //                     ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
    // -----------------------------------------------------------------------------------------------------------

	// Throw exception if token is not set (this check is here just for the sake of newcomers, you can remove it)
	if (strpos($restPkiAccessToken, ' API ') !== false) {
		throw new Exception('The API access token was not set! Hint: to run this sample you must generate an API access token on the REST PKI website and paste it on the file util.php');
	}

	// -----------------------------------------------------------------------------------------------------------
	// IMPORTANT NOTICE: in production code, you should use HTTPS to communicate with REST PKI, otherwise your API
	// access token, as well as the documents you sign, will be sent to REST PKI unencrypted.
	// -----------------------------------------------------------------------------------------------------------
	$restPkiUrl = 'http://pki.rest/';
	//$restPkiUrl = 'https://pki.rest/'; // <--- USE THIS IN PRODUCTION!

	return new RestPkiClient($restPkiUrl, $restPkiAccessToken);
}

function setNoCacheHeaders() {
	header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 3600) . ' GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: private, no-store, max-age=0, no-cache, must-revalidate, post-check=0, pre-check=0');
	header('Pragma: no-cache');
}

function createAppData() {
	$appDataPath = "app-data";
	if (!file_exists($appDataPath)) {
		mkdir($appDataPath);
	}
}

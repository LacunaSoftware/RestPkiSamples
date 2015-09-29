<?php

require_once 'RestPki.php';

use Lacuna\RestPkiClient;

function getRestPkiClient() {

	// -------------------------------------------------------------------------------------------
	// PASTE YOUR ACCESS TOKEN BELOW
	// -------------------------------------------------------------------------------------------
	//
	$restPkiAccessToken = '';
	//                    ^^----- API access token goes here
	// -------------------------------------------------------------------------------------------
	
	// Throw exception if token is not set, this check is here just for the sake of newcomers, you 
	// can remove it.
	if (empty($restPkiAccessToken)) {
		throw new \Exception('The API access token was not set! Hint: to run this sample you must generate an API access token on the REST PKI website and paste it on the file api/util.php');
	}
	
	return new RestPkiClient('https://pki.rest/', $restPkiAccessToken);
}

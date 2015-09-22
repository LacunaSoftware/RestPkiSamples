<?php

require_once 'RestPki.php';

use Lacuna\RestPkiClient;

function getRestPkiClient() {
	// -------------------------------------------------------------------------------------------
	$restPkiAccessToken = 'PASTE YOUR ACCESS TOKEN HERE';
	// -------------------------------------------------------------------------------------------
	return new RestPkiClient('https://restpki.lacunasoftware.com/', $restPkiAccessToken);
}

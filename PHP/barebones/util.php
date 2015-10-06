<?php

require_once 'RestPki.php';

use Lacuna\RestPkiClient;

function getRestPkiClient() {

	// -------------------------------------------------------------------------------------------
	// PASTE YOUR ACCESS TOKEN BELOW
	//$restPkiAccessToken = 'PLACE YOUR API ACCESS TOKEN HERE';
	//                     ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
	// -------------------------------------------------------------------------------------------
	$restPkiAccessToken = 'AgmDHHzhfpRLb80JbrKM4sQy0vcRmUVywRdKlrGFl3UIi-n7e_wEldGh1lPQelBhKPsvnk9V2jRMV-OC5Vuq2nqrdlf9nWMZVeU3giXuewW6DLsic21n8q3SCZu8-H5S02sLJWXN2_T2jOxQDY_a4v4Pvs1yiDZOrWX_uNZ5hmYnQrJmbcb5WhHk87DBP72r_MYV0pF9h4EIa-HujU1zETjITS5yh7ZRGda86rIS6zITghZ3qWm3zsihLRXoN-stpxXnpmcf7j6x16MqHbHKsoBBxdXo5zZLXL-L9Q-cftiCD_xe3FVV_D6Zd8wnjnkvbP6hmicYE1wdUbDFvdeqq9yi69-Y_mKsAcLUIGxHtCskepMQna4Dgn7CaSKs1sKWF0Iv4xpbRLMkEzQZcBo-7r-4p1BDipwAck9otBKTgoz_P851w6xlLJj1yuFq4cxr2rkVnZLB5oWLe0w5fr0xDo3CvIDn3P0NfBWg7lMxyBNiYMDhYJ-CMr6idBCB6mbQI_wREw';

	// Throw exception if token is not set, this check is here just for the sake of newcomers, you 
	// can remove it.
	if (strpos($restPkiAccessToken, ' API ') !== false) {
		throw new \Exception('The API access token was not set! Hint: to run this sample you must generate an API access token on the REST PKI website and paste it on the file util.php');
	}
	
	return new RestPkiClient('http://pki.rest/', $restPkiAccessToken);
}

function setExpiredPage() {
	header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 3600) . ' GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: private, no-store, max-age=0, no-cache, must-revalidate, post-check=0, pre-check=0');
	header('Pragma: no-cache');
}

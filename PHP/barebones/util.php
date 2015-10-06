<?php

require_once 'RestPki.php';

use Lacuna\RestPkiClient;

function getRestPkiClient() {

	// -------------------------------------------------------------------------------------------
	// PASTE YOUR ACCESS TOKEN BELOW
	$restPkiAccessToken = 'JWNYwIKpG81L1V4tfWQLnbVBAk2BkUe_gwJhLS7ADUbKrjaJ82MvPlL9IJfL0knheP0WDi8Yp6G6pq_cNKhnVL-Pb0Bo1UlEi9O6-V466Y3EsdF3bTBGkKfhq1HMqXU6Kio48rLrMDHEM0ezF3wzR21gpfMtZX8cjvKsJqimRWOY71dR2UFaZfNu9YwXEUU4kFSxvVQy1aA1GnDZwY-qrhfzPoi1-ReIu4pegcUtallr5CQoZ8BNpIBSbk-4vQ3IWEGfm5dhgjnLTga0g8-FmuPwFv2TZglXYHfrJHXp-B8v0Ijr-qh6sf4FtwK-28Ek1VEhcZDfL9cH4fOP4odOneI4hI-vVzx2sKqcm94oPIX8cRxfmgiX6eP80bvczGG0rZm2PJHR_y6v2X1Fr-6u_1ibBioeHFgm17Tb9y5KMkYgvkx3cqMqi51OxXSS0aCUcvl6K3vQQPeqoiVx-ZpImTzWHllOtvMSqqR0-4bWJg4sroqNkfcyFIK7uyjqJyEoSg8Txw';
	//                     ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
	// -------------------------------------------------------------------------------------------

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

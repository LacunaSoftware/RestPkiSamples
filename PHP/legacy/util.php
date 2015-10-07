<?php

require_once 'RestPkiLegacy.php';

use Lacuna\RestPkiClient;

function getRestPkiClient() {

	// -----------------------------------------------------------------------------------------------------------
	// PASTE YOUR ACCESS TOKEN BELOW
	//$restPkiAccessToken = 'PLACE YOUR API ACCESS TOKEN HERE';
	//                     ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
	// -----------------------------------------------------------------------------------------------------------
	$restPkiAccessToken = 'prP404HtikMCNPhsemUkAcLQnQH1aSl9mbDX53DEWoAje5DL5-lmYOKEUCVou8LcQ-i6g5MG9R96XAxVDTeUzapnq6wtxFb5bpYwFARAT4If6vpp-7iftgTFfhifyBjYgxK5dWmM79jFKSFSfg0lkHqJqpyrxPln1z3dh2WhZGZZO66jV1bD1DzeMNXzYorvgQ2LHy_7JeZFHhoDzDjoxT_ZJizBJ47yUH397sHNrTeLsvurqtV10dIcy3PF1XHfnhGtE-AjnVMJ8dOZvnaiXDr-pJcVY1xdOcTNZqiUTjXgLWDYg8WORazc0AQxtvWFj-MfucW1IHd7hd-vYjp_LMMe4ghCuAPsPSabElAi4l5lUkk5_4rLQ95VKtCM7M51KtXUrxExLxFyQEYqfWrCOvn06qByKnkOgM9yQ28twlCzf9f0dzVpf1BSlktPpO97oUXL72fUKEXEoXgHoof4reFRnXThfvkPVsKkn_ILh3XQQ_UIz_-2NbigWI_9G9hTi1fy2A';

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

function setExpiredPage() {
	header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 3600) . ' GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: private, no-store, max-age=0, no-cache, must-revalidate, post-check=0, pre-check=0');
	header('Pragma: no-cache');
}

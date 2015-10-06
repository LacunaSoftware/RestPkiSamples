<?php

require_once 'RestPki.php';

use Lacuna\RestPkiClient;

function getRestPkiClient() {

	// -------------------------------------------------------------------------------------------
	// PASTE YOUR ACCESS TOKEN BELOW
	//$restPkiAccessToken = 'PLACE YOUR API ACCESS TOKEN HERE';
	//                     ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
	// -------------------------------------------------------------------------------------------
	$restPkiAccessToken = 'jXmd_vkcJwNAu6cDfEEYrZXJz0vtB0XSacfBZk9H-Ee3TVnvW5Pt--xrpDJfc26zar2CrSRmVZzU5laOhuM1UeO22AiYk9BFn_mUmU_byrh9Sn_Jed334F-tw7s0QtKCVEZQxH_8AsBnavHSd-tTqQy1x91P5Sgs5FDApbJ9VtCdA7P8S8vwVCwXRso2vPDaL38OkzSMHyEzDU_SWwLudPkxM5Nh-6V6EcLd5snNRG40aUx5umppc-NSMbY2WPOhj7Fc2qpB95YxTxIsUFueL4_nfbEDPyUiXGM6zSgfaaUr_AiflFBTzqo0Ed6mzf8Ky8e8Cre2J7sZF_cSE16GFEJx3c77G50v2RAiN3cJ_A5Kq5dHqmsFRyrp4ZVAQahGsPcMNCNUJresaPtAzINFvakJEpAUSMx4cwsyLU1-ebo5Uq06o3Kc1WEqxzavv7jpR6Xu6eWOTM2_AN-aEF0S564mpFefaH3LFRPDaxfheG1Z2U7Zrv2E9kSOVabzYZ3MYpSXJQ';
	
	// Throw exception if token is not set, this check is here just for the sake of newcomers, you 
	// can remove it.
	if (strpos($restPkiAccessToken, ' API ') !== false) {
		throw new \Exception('The API access token was not set! Hint: to run this sample you must generate an API access token on the REST PKI website and paste it on the file api/util.php');
	}

	// -------------------------------------------------------------------------------------------
	// IMPORTANT NOTICE: in production code, you should use HTTPS, otherwise your API access token,
	// as well as your documents, will be sent to REST PKI unencrypted.
	// -------------------------------------------------------------------------------------------
	return new RestPkiClient('http://pki.rest/', $restPkiAccessToken);
	//return new RestPkiClient('https://pki.rest/', $restPkiAccessToken); // <--- USE THIS IN PRODUCTION!
}

function setExpiredPage() {
	header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 3600) . ' GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: private, no-store, max-age=0, no-cache, must-revalidate, post-check=0, pre-check=0');
	header('Pragma: no-cache');
}

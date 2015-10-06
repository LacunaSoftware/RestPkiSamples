<?php

/*
 * This file contains the server-side logic for the authentication example. The client-side is implemented at:
 * - View: /authentication.php (at project root)
 * - JS: content/js/app/authentication.js
 */

// The file RestPki.php contains the helper classes to call the REST PKI API
require_once 'RestPki.php';

// The file util.php contains the API access token
require_once 'util.php';

try {

	// Depending on the method of the HTTP request (GET or POST), we'll call the corresponding function
	// and output its result as JSON
	$method = $_SERVER['REQUEST_METHOD'];
	switch ($method) {
		case 'GET':
			$response = get();
			break;
		case 'POST':
			$response = post();
			break;
		default:
			die('method not allowed');
			break;
	}
	header('Content-Type: application/json');
	echo json_encode($response);

} catch (Exception $e) {

	header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
	header('Content-Type: application/json');
	echo json_encode([
		'message' => $e->getMessage()
	]);

}

/*
 * GET api/authentication.php (called via AJAX)
 *
 * This action is called once the user clicks the "Sign In" button.
 */
function get() {

	// Get an instantiate of the Authentication class
	$auth = getRestPkiClient()->getAuthentication();
	
	// Call the Authentication startWithWebPki() method, which initiates the authentication. This yields the token,
	// a 22-character case-sensitive URL-safe string, which we'll send to the page in order to pass on the
	// signWithRestPki method of the Web PKI component.
	$token = $auth->startWithWebPki(\Lacuna\StandardSecurityContexts::PKI_BRAZIL);
		
	// Note: By changing the SecurityContext above you can accept only certificates from a certain PKI,
	// for instance, ICP-Brasil (\Lacuna\StandardSecurityContexts::PKI_BRAZIL).
		
	// Return the token as response
	return $token;
}


/*
 * POST api/authentication.php?token=xxx (called via AJAX)
 *
 * This action is called after signing the nonce on the client-side with the user's certificate. We'll once
 * again use the Authentication class to do the actual work.
 */
function post() {

	// Get the token, passed back to us by the page on the URL
	$token = $_GET['token'];
	
	// Get an instantiate of the Authentication class
	$auth = getRestPkiClient()->getAuthentication();
	
	// Call the completeWithWebPki() method, which finalizes the authentication process. It receives as input
	// only the token that was yielded previously (which we sent to the page and the page sent us back on the URL).
	// The call yields a ValidationResults which denotes whether the authentication was successful or not.
	$vr = $auth->completeWithWebPki($token);
	
	// Check the authentication result
	if (!$vr->isValid()) {
		// If the authentication failed, inform the page
		return [
			'success' => false,
			'message' => 'Authentication failed',
			'validationResults' => (string)$vr
		];
	}
	
	// At this point, you have assurance that the certificate is valid according to the
	// SecurityContext passed on the first step (see function get()) and that the user is indeed the certificate's
	// subject. Now, you'd typically query your database for a user that matches one of the
	// certificate's fields, such as $userCert->emailAddress or $userCert->pkiBrazil->cpf (the actual field
	// to be used as key depends on your application's business logic) and set the user
	// as authenticated with whatever web security framework your application uses.
	// For demonstration purposes, we'll just return a success and put on the message something
	// to show that we have access to the certificate's fields.
	
	$userCert = $auth->getCertificate();
	$message = "Welcome, {$userCert->subjectName->commonName}!";
	if (!empty($userCert->emailAddress)) {
		$message .= " Your email address is {$userCert->emailAddress}";
	}
	if (!empty($userCert->pkiBrazil->cpf)) {
		$message .= " and your CPF is {$userCert->pkiBrazil->cpf}";
	}

	return [
		'success' => true,
		'message' => $message
	];
}

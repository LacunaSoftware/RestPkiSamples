<?php

require_once 'RestPki.php';
require_once 'util.php';

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

function get() {
	$auth = getRestPkiClient()->getAuthentication();
	$token = $auth->startWithWebPki(\Lacuna\StandardSecurityContexts::PKI_BRAZIL);
	return $token;
}

function post() {
	$token = $_GET['token'];
	$auth = getRestPkiClient()->getAuthentication();
	$vr = $auth->completeWithWebPki($token);
	if (!$vr->isValid()) {
		return [
			'success' => false,
			'message' => 'Authentication failed',
			'validationResults' => (string)$vr
		];
	}

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

<?php

require_once 'RestPki.php';
require_once 'util.php';

use Lacuna\PadesSignatureStarter;
use Lacuna\PadesSignatureFinisher;

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
	$signatureStarter = new PadesSignatureStarter(getRestPkiClient());
	$signatureStarter->setPdfToSignContent(file_get_contents("../content/SampleDocument.pdf"));
	$signatureStarter->setSignaturePolicy(\Lacuna\StandardSignaturePolicies::PADES_BASIC);
	$signatureStarter->setSecurityContext(\Lacuna\StandardSecurityContexts::PKI_BRAZIL);
	$token = $signatureStarter->startWithWebPki();
	return $token;
}

function post() {
	$token = $_GET['token'];
	$signatureFinisher = new PadesSignatureFinisher(getRestPkiClient());
	$signatureFinisher->setToken($token);
	$signedPdf = $signatureFinisher->finish();
	$id = uniqid();
	if (!file_exists('../app-data')) {
		mkdir('../app-data');
	}
	file_put_contents("../app-data/{$id}.pdf", $signedPdf);
	return [
		'success' => true,
		'signedFileUrl' => "app-data/{$id}.pdf"
	];
}

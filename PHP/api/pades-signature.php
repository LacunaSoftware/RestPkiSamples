<?php

require_once 'RestPki.php';
require_once 'util.php';

use Lacuna\PadesSignatureStarter;
use Lacuna\PadesSignatureFinisher;
use Lacuna\PadesVisualPositioningPresets;

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


	$signatureStarter->setVisualRepresentation([
		'text' => [
			'text' => 'Signed by {{signerName}}',
			'includeSigningTime' => true
		],
		'image' => [
			'resource' => [
				'content' => base64_encode(file_get_contents('../content/PdfStamp.png')),
				'mimeType' => 'image/png'
			],
			'opacity' => 50,
			'horizontalAlign' => 'Right'
		],
		'position' => getVisualRepresentationPosition(4)
	]);
	$token = $signatureStarter->startWithWebPki();
	return $token;
}

function getVisualRepresentationPosition($sampleNumber) {

	switch ($sampleNumber) {

		case 1:
			return PadesVisualPositioningPresets::getFootnote(getRestPkiClient());

		case 2:
			$visualPosition = PadesVisualPositioningPresets::getFootnote(getRestPkiClient());
			$visualPosition->auto->container->bottom = 3;
			return $visualPosition;

		case 3:
			return [
				'pageNumber' => -1,
				'measurementUnits' => 'Centimeters',
				'manual' => [
					'left' => 2.54,
					'bottom' => 2.54,
					'width' => 5,
					'height' => 3
				]
			];

		case 4:
			return [
				'pageNumber' => -1,
				'measurementUnits' => 'Centimeters',
				'auto' => [
					'container' => [
						'left' => 2.54,
						'right' => 2.54,
						'bottom' => 2.54,
						'height' => 12.31
					],
					'signatureRectangleSize' => [
						'width' => 5,
						'height' => 3
					],
					'rowSpacing' => 1
				]
			];

		default:
			return null;
	}
}

function post() {
	$token = $_GET['token'];
	$signatureFinisher = new PadesSignatureFinisher(getRestPkiClient());
	$signatureFinisher->setToken($token);
	$signedPdf = $signatureFinisher->finish();
	$id = uniqid();
	$appDataPath = "../app-data";
	if (!file_exists($appDataPath)) {
		mkdir($appDataPath);
	}
	file_put_contents("{$appDataPath}/{$id}.pdf", $signedPdf);
	return [
		'success' => true,
		'signedFileUrl' => "app-data/{$id}.pdf"
	];
}

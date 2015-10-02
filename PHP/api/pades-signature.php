<?php

/*
 * This file contains the server-side logic for the PAdES signature example. The client-side is implemented at:
 * - View: /pades-signature.php (at project root)
 * - JS: content/js/app/pades-signature.js
 */

// The file RestPki.php contains the helper classes to call the REST PKI API
require_once 'RestPki.php';

// The file util.php contains the API access token
require_once 'util.php';

use Lacuna\PadesSignatureStarter;
use Lacuna\PadesSignatureFinisher;
use Lacuna\PadesVisualPositioningPresets;

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
 * GET api/pades-signature.php (called via AJAX)
 *
 * This action is called by the page to initiate the signature process.
 */
function get() {

	// Instantiate the PadesSignatureStarter class, responsible for receiving the signature elements and start the
	// signature process
	$signatureStarter = new PadesSignatureStarter(getRestPkiClient());
	
	// Set the PDF to be signed, which in the case of this example is a fixed sample document
	$signatureStarter->setPdfToSignContent(file_get_contents("../content/SampleDocument.pdf"));
	
	// Set the signature policy
	$signatureStarter->setSignaturePolicy(\Lacuna\StandardSignaturePolicies::PADES_BASIC);
	
	// Set a SecurityContext to be used to determine trust in the certificate chain
	$signatureStarter->setSecurityContext(\Lacuna\StandardSecurityContexts::PKI_BRAZIL);
	// Note: By changing the SecurityContext above you can accept only certificates from a certain PKI,
	// for instance, ICP-Brasil (\Lacuna\StandardSecurityContexts::PKI_BRAZIL).

	// Set the visual representation for the signature
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
		'position' => getVisualRepresentationPosition(4) // changing this number will result in different examples of signature positioning being used
	]);
	
	// Call the startWithWebPki, which initiates the signature. This yields the token,
	// a 43-character case-sensitive URL-safe string, which we'll send to the page in order to pass on the
	// signWithRestPki method of the Web PKI component.
	$token = $signatureStarter->startWithWebPki();
	
	// Return the token as response
	return $token;
}

// This function is called by the get() function. It contains examples of signature visual representation positionings.
function getVisualRepresentationPosition($sampleNumber) {

	switch ($sampleNumber) {

		case 1:
			// Example #1: automatic positioning on footnote. This will insert the signature, and future signatures, 
			// ordered as a footnote of the last page of the document
			return PadesVisualPositioningPresets::getFootnote(getRestPkiClient());

		case 2:
			// Example #2: get the footnote positioning preset and customize it
			$visualPosition = PadesVisualPositioningPresets::getFootnote(getRestPkiClient());
			$visualPosition->auto->container->bottom = 3;
			return $visualPosition;

		case 3:
			// Example #3: manual positioning
			return [
				'pageNumber' => -1, // negative numbers represent counting from end of the document (-1 is last page)
				'measurementUnits' => 'Centimeters',
				// define a manual position of 5cm x 3cm, positioned at 1 inch from  the left and bottom margins
				'manual' => [
					'left' => 2.54,
					'bottom' => 2.54,
					'width' => 5,
					'height' => 3
				]
			];

		case 4:
			// Example #4: auto positioning
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

/*
 * POST api/pades-signature?token=xxx
 *
 * This action is called once the signature is complete on the client-side. The page sends back on the URL the token
 * originally yielded by the get() method.
 */
function post() {

	// Get the token, passed back to us by the page on the URL
	$token = $_GET['token'];
	
	// Instantiate the PadesSignatureFinisher class, responsible for completing the signature process
	$signatureFinisher = new PadesSignatureFinisher(getRestPkiClient());
	
	// Set the token previously yielded by the startWithWebPki() method (which we sent to the page and the page
	// sent us back on the URL)
	$signatureFinisher->setToken($token);
	
	// Call the finish() method, which finalizes the signature process and returns the signed PDF
	$signedPdf = $signatureFinisher->finish();
	
	// At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
	 // store the PDF on a temporary folder publicly accessible and return its URL to the page.
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

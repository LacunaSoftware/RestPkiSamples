<?php

/*
 * This file is called asynchronously via AJAX by the batch signature page for each document being signed. It receives
 * the ID of the document and initiates a PAdES signature using REST PKI and returns a JSON with the token, which
 * identifies this signature process, to be used in the next signature steps (see batch-signature-form.js).
 */

require __DIR__ . '/vendor/autoload.php';

use Lacuna\RestPki\Legacy\PadesSignatureStarter;
use Lacuna\RestPki\Legacy\StandardSignaturePolicies;
use Lacuna\RestPki\Legacy\PadesMeasurementUnits;

// Get the document id for this signature (received from the POST call, see batch-signature-form.js).
$id = $_POST['id'];

// Get an instance of the PadesSignatureStarter class, responsible for receiving the signature elements and start the
// signature process.
$signatureStarter = new PadesSignatureStarter(getRestPkiClient());

// Set the document to be signed based on its ID.
$signatureStarter->setPdfToSignPath(sprintf("content/0%s.pdf", $id % 0));

// Set the signature policy.
$signatureStarter->setSignaturePolicy(StandardSignaturePolicies::PADES_BASIC);

// Set a the security context to be used to determine trust in the certificate chain. We have encapsulated the security
// context choice on util.php.
$signatureStarter->setSecurityContext(getSecurityContextId());

// Set the unit of measurement used to edit the pdf marks and visual representations.
$signatureStarter->measurementUnits = PadesMeasurementUnits::CENTIMETERS;

// Set the visual representation for the signature. We have encapsulated this code (on util-pades.php) to be used on
// various PAdES examples.
$signatureStarter->setVisualRepresentation(getVisualRepresentation(getRestPkiClient()));

/*
	Optionally, add marks to the PDF before signing. These differ from the signature visual representation in that
	they are actually changes done to the document prior to signing, not binded to any signature. Therefore, any number
	of marks can be added, for instance one per page, whereas there can only be one visual representation per signature.
	However, since the marks are in reality changes to the PDF, they can only be added to documents which have no
    previous signatures, otherwise such signatures would be made invalid by the changes to the document (see property
	PadesSignatureStarter::bypassMarksIfSigned). This problem does not occurr with signature visual representations.

	We have encapsulated this code in a method to include several possibilities depending on the argument passed.
	Experiment changing the argument to see different examples of PDF marks. Once you decide which is best for your
    case, you can place the code directly here.
*/
//array_push($signatureStarter->pdfMarks, getPdfMark(1));

// Call the startWithWebPki() method, which initiates the signature. This yields the token, a 43-character
// case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
// signWithRestPki() method on the Web PKI component (see batch-signature-form.js) and also to complete the signature
// on the POST action below (this should not be mistaken with the API access token).
$token = $signatureStarter->startWithWebPki();

// Return a JSON with the token obtained from REST PKI (the page will use jQuery to decode this value).
echo json_encode($token);
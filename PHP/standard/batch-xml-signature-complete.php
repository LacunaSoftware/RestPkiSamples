<?php

/*
 * This file receives the form submission from xml-element-signature.php. We'll call REST PKI to complete the signature.
 */

require __DIR__ . '/vendor/autoload.php';

use Lacuna\RestPki\XmlSignatureFinisher;

// Get the token for this signature (rendered in a hidden input field, see xml-element-signature.php)
$token = $_POST['token'];

// Get the document id for this signature (received from the POST call, see batch-signature-form.js), if not
if (array_key_exists('fileId', $_POST)) {
    $fileId = $_POST['fileId'];
} else {
    $fileId = uniqid() . ".xml";
}

// Instantiate the XmlSignatureFinisher class, responsible for completing the signature process
$signatureFinisher = new XmlSignatureFinisher(getRestPkiClient());

// Set the token
$signatureFinisher->token = $token;

// Call the finish() method, which finalizes the signature process and returns the signed XML
$signedXml = $signatureFinisher->finish();

// Get information about the certificate used by the user to sign the file. This method must only be called after
// calling the finish() method.
$signerCert = $signatureFinisher->getCertificateInfo();

// At this point, you'd typically store the signed XML on your database. For demonstration purposes, we'll
// store the PDF on a temporary folder publicly accessible and render a link to it.

createAppData(); // make sure the "app-data" folder exists (util.php)
file_put_contents("app-data/$fileId", $signedXml);

// Return a JSON with the signed file name obtained from REST PKI (the page will use jQuery to decode this value)
echo json_encode($fileId);
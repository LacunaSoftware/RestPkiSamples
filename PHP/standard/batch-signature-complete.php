<?php

/*
 * This file is called asynchronously via AJAX by the batch signature page for each document being signed. It receives
 * the token, that identifies the signature process. We'll call REST PKI to complete this signature and return a JSON
 * with the saved filename so that the page can render a link to it.
 */

require __DIR__ . '/vendor/autoload.php';

use Lacuna\RestPki\PadesSignatureFinisher2;

// Get the token for this signature (received from the post call, see batch-signature-form.js).
$token = $_POST['token'];

// Instantiate the PadesSignatureFinisher2 class, responsible for completing the signature process.
$signatureFinisher = new PadesSignatureFinisher2(getRestPkiClient());

// Set the token.
$signatureFinisher->token = $token;

// Call the finish() method, which finalizes the signature process and returns a SignatureResult object.
$signatureResult = $signatureFinisher->finish();

// The "certificate" property of the SignatureResult object contains information about the certificate used by the user
// to sign the file.
$signerCert = $signatureResult->certificate;

// At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
// store the PDF on a temporary folder publicly accessible and render a link to it (see batch-signature-form.js).

$filename = uniqid() . ".pdf";
createAppData(); // Make sure the "app-data" folder exists (util.php).

// The SignatureResult object has functions for writing the signature file to a local file (writeToFile()) and to get
// its raw contents (getContent()). For large files, use writeToFile() in order to avoid memory allocation issues.
$signatureResult->writeToFile("app-data/{$filename}");

// Return a JSON with the file name obtained from REST PKI (the page will use jQuery to decode this value).
echo json_encode($filename);

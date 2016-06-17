<?php

/*
 * This file receives the token, that identifies the signature process. We'll call REST PKI to 
 * complete this signature.
 */

// The file RestPkiLegacy.php contains the helper classes to call the REST PKI API for PHP 5.3+. Notice: if you're using
// PHP version 5.5 or greater, please use one of the other samples, which make better use of the extended capabilities
// of the newer versions of PHP - https://github.com/LacunaSoftware/RestPkiSamples/tree/master/PHP
require_once 'RestPkiLegacy.php';

// The file util.php contains the function getRestPkiClient(), which gives us an instance of the RestPkiClient class
// initialized with the API access token
require_once 'util.php';

use Lacuna\PadesSignatureFinisher;

// Get the token for this signature (received from the post call, see batch-signature-form.php)
$token = $_POST['token'];

// Instantiate the PadesSignatureFinisher class, responsible for completing the signature process
$signatureFinisher = new PadesSignatureFinisher(getRestPkiClient());

// Set the token
$signatureFinisher->setToken($token);

// Call the finish() method, which finalizes the signature process and returns the signed PDF
$signedPdf = $signatureFinisher->finish();

// Get information about the certificate used by the user to sign the file. This method must only be called after
// calling the finish() method.
$signerCert = $signatureFinisher->getCertificate();

// At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
// store the PDF on a temporary folder publicly accessible and render a link to it (see batch-signature-form.js).

$filename = uniqid() . ".pdf";
createAppData(); // make sure the "app-data" folder exists (util.php)
file_put_contents("app-data/{$filename}", $signedPdf);

// Return a JSON with the file name obtained from REST PKI (the page will use jQuery to decode this value)
echo json_encode($filename);
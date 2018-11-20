<?php

/*
 * This file is called asynchronously via AJAX by the batch signature page for each document being signed. It receives
 * the ID of the document and initiates a PAdES signature using REST PKI and returns a JSON with the token, which
 * identifies this signature process, to be used in the next signature steps (see batch-signature-form.js).
 */

// The file RestPkiLegacy52.php contains the helper classes to call the REST PKI API for PHP 5.2+. Notice: if you're
// using PHP version 5.3 or greater, please use one of the other samples, which make better use of the extended
// capabilities of the newer versions of PHP - https://github.com/LacunaSoftware/RestPkiSamples/tree/master/PHP.
require_once 'RestPkiLegacy52.php';

// The file util.php contains the functions getRestPkiClient(), which gives us an instance of
// the RestPkiClient class initialized with the API access token.
require_once 'util.php';
require_once 'util-pades.php';

// Get the document id for this signature (received from the POST call, see batch-signature-form.js).
$id = $_POST['id'];

// Instantiate the RestPkiPadesSignatureStarter class, responsible for receiving the signature elements and start the
// signature process.
$signatureStarter = new RestPkiPadesSignatureStarter(getRestPkiClient());

// Set the document to be signed based on its ID.
$signatureStarter->setPdfToSignPath('content/0' . ($id % 10) . '.pdf');

// Set the signature policy.
$signatureStarter->setSignaturePolicy(RestPkiStandardSignaturePolicies::PADES_BASIC);

// Set the security context to be used to determine trust in the certificate chain. We have encapsulated the security
// context choice on util.php.
$signatureStarter->setSecurityContext(getSecurityContextId());

// Set the visual representation to the signature. We have encapsulated this code (on util-pades.php) to be used on
// various PAdES examples.
$signatureStarter->setVisualRepresentation(getVisualRepresentation(getRestPkiClient()));

// Call the startWithWebPki() method, which initiates the signature. This yields the token, a 43-character
// case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
// signWithRestPki() method on the Web PKI component (see batch-signature-form.js) and also to complete the signature
// on the POST action below (this should not be mistaken with the API access token).
$token = $signatureStarter->startWithWebPki();

// Return a JSON with the token obtained from REST PKI (the page will use jQuery to decode this value).
echo json_encode($token);
<?php

/*
 * This file is called asynchronously via AJAX by the batch signature page for each XML element being signed. It
 * receives the ID of the element and the document to be signed. It initiates a XML element signature using REST PKI
 * and returns a JSON with the token, which identifies this signature process, to be used in the next signature steps
 * (see batch-xml-element-signature-form.js).
 */

require __DIR__ . '/vendor/autoload.php';

use Lacuna\RestPki\XmlElementSignatureStarter;
use Lacuna\RestPki\StandardSecurityContexts;
use Lacuna\RestPki\StandardSignaturePolicies;

// Get the element id for this signature (received from the POST call, see batch-xml-element-signature-form.js)
$elemId = $_POST['elemId'];

// Get the document id for this signature (received from the POST call, see batch-xml-element-signature-form.js)
if (array_key_exists('fileId', $_POST)) {
    $fileId = $_POST['fileId'];
}

// Instantiate the XmlElementSignatureStarter class, responsible for receiving the signature elements and start the
// signature process
$signatureStarter = new XmlElementSignatureStarter(getRestPkiClient());

if (empty($fileId)) {
    // Set the XML to be initially signed, a sample Brazilian fiscal invoice pre-generated
    $signatureStarter->setXmlToSignFromPath("content/EventoManifesto.xml");
} else {
    // If the XML file is already signed, we pass the signed file instead of the original file
    $signatureStarter->setXmlToSignFromPath("app-data/$fileId");
}

// Set the ID of the element to be signed.
$signatureStarter->toSignElementId = $elemId;

// Set the signature policy.
$signatureStarter->signaturePolicy = StandardSignaturePolicies::XML_ICPBR_NFE_PADRAO_NACIONAL;

// Optionally, set a SecurityContext to be used to determine trust in the certificate chain. Since we're using the
// XML_ICPBR_NFE_PADRAO_NACIONAL policy, the security context will default to PKI Brazil (ICP-Brasil)
//$signatureStarter->securityContext = StandardSecurityContexts::PKI_BRAZIL;
// Note: By changing the SecurityContext above you can accept only certificates from a custom PKI for tests.

// Call the startWithWebPki() method, which initiates the signature. This yields the token, a 43-character
// case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
// signWithRestPki() method on the Web PKI component (see javascript below) and also to complete the signature after
// the form is submitted (see file xml-element-signature-action.php). This should not be mistaken with the API access token.
$token = $signatureStarter->startWithWebPki();

// Return a JSON with the token obtained from REST PKI (the page will use jQuery to decode this value)
echo json_encode($token);
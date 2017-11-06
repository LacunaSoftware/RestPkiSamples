<?php

/*
 * This file is called asynchronously via AJAX by the batch signature page for each document being signed. It receives
 * the ID of the document and initiates a PAdES signature using REST PKI and returns a JSON with the token, which
 * identifies this signature process, to be used in the next signature steps (see batch-signature-form.js).
 */

require __DIR__ . '/vendor/autoload.php';

use Lacuna\RestPki\PadesSignatureStarter;
use Lacuna\RestPki\StandardSignaturePolicies;
use Lacuna\RestPki\PadesMeasurementUnits;
use Lacuna\RestPki\StandardSecurityContexts;

// Get the document id for this signature (received from the POST call, see batch-signature-form.js)
$id = $_POST['id'];

// Instantiate the PadesSignatureStarter class, responsible for receiving the signature elements and start the
// signature process
$signatureStarter = new PadesSignatureStarter(getRestPkiClient());

// Set the document to be signed based on its ID
$signatureStarter->setPdfToSignFromPath("content/{$id}.pdf");

// Set the unit of measurement used to edit the pdf marks and visual representations
$signatureStarter->measurementUnits = PadesMeasurementUnits::CENTIMETERS;

// Set the signature policy
$signatureStarter->signaturePolicy = StandardSignaturePolicies::PADES_BASIC;

// Set a SecurityContext to be used to determine trust in the certificate chain
$signatureStarter->securityContext = StandardSecurityContexts::PKI_BRAZIL;
// Note: By changing the SecurityContext above you can accept only certificates from a certain PKI, for instance,
// ICP-Brasil (\Lacuna\StandardSecurityContexts::PKI_BRAZIL).

// Set the visual representation for the signature
$signatureStarter->visualRepresentation = [

    'text' => [

        // The tags {{signerName}} and {{signerNationalId}} will be substituted according to the user's certificate
        // signerName -> full name of the signer
        // signerNationalId -> if the certificate is ICP-Brasil, contains the signer's CPF
        'text' => 'Signed by {{signerName}} ({{signerNationalId}})',
        // Specify that the signing time should also be rendered
        'includeSigningTime' => true,
        // Optionally set the horizontal alignment of the text ('Left' or 'Right'), if not set the default is Left
        'horizontalAlign' => 'Left',
        // Optionally set the container within the signature rectangle on which to place the text. By default, the
        // text can occupy the entire rectangle (how much of the rectangle the text will actually fill depends on the
        // length and font size). Below, we specify that the text should respect a right margin of 1.5 cm.
        'container' => [
            'left' => 0,
            'top' => 0,
            'right' => 1.5,
            'bottom' => 0
        ]
    ],
    'image' => [

        // We'll use as background the image content/PdfStamp.png
        'resource' => [
            'content' => base64_encode(file_get_contents('content/PdfStamp.png')),
            'mimeType' => 'image/png'
        ],
        // Opacity is an integer from 0 to 100 (0 is completely transparent, 100 is completely opaque).
        'opacity' => 50,
        // Align the image to the right
        'horizontalAlign' => 'Right',
        // Align the image to the center
        'verticalAlign' => 'Center',

    ],
    // Position of the visual representation. We have encapsulated this code in a function to include several
    // possibilities depending on the argument passed to the function. Experiment changing the argument to see
    // different examples of signature positioning. Once you decide which is best for your case, you can place the
    // code directly here. See file util-pades.php
    'position' => getVisualRepresentationPosition(7)
];

/*
	Optionally, add marks to the PDF before signing. These differ from the signature visual representation in that
	they are actually changes done to the document prior to signing, not binded to any signature. Therefore, any number
	of marks can be added, for instance one per page, whereas there can only be one visual representation per signature.
	However, since the marks are in reality changes to the PDF, they can only be added to documents which have no previous
	signatures, otherwise such signatures would be made invalid by the changes to the document (see property
	PadesSignatureStarter.BypassMarksIfSigned). This problem does not occurr with signature visual representations.

	We have encapsulated this code in a method to include several possibilities depending on the argument passed.
	Experiment changing the argument to see different examples of PDF marks. Once you decide which is best for your case,
	you can place the code directly here.
*/
//array_push($signatureStarter->pdfMarks, getPdfMark(1));

// Call the startWithWebPki() method, which initiates the signature. This yields the token, a 43-character
// case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
// signWithRestPki() method on the Web PKI component (see batch-signature-form.js) and also to complete the signature
// on the POST action below (this should not be mistaken with the API access token).
$token = $signatureStarter->startWithWebPki();

// Return a JSON with the token obtained from REST PKI (the page will use jQuery to decode this value)
echo json_encode($token);
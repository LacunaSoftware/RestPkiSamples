<?php

/*
 * This file initiates a PAdES signature using REST PKI and returns a token, that identifies this
 * signature process, to be used in the next signature steps (see batch-signature-form.js).
 */

// The file RestPki.php contains the helper classes to call the REST PKI API
require_once 'RestPki.php';

// The file util.php contains the function getRestPkiClient(), which gives us an instance of the RestPkiClient class
// initialized with the API access token
require_once 'util.php';

use Lacuna\PadesSignatureStarter;
use Lacuna\PadesVisualPositioningPresets;
use Lacuna\StandardSecurityContexts;
use Lacuna\StandardSignaturePolicies;

// This function is called below. It contains examples of signature visual representation positionings. This code is
// only in a separate function in order to organize the various examples, you can pick the one that best suits your
// needs and use it below directly without an encapsulating function.
function getVisualRepresentationPosition($sampleNumber) {

    switch ($sampleNumber) {

        case 1:
            // Example #1: automatic positioning on footnote. This will insert the signature, and future signatures,
            // ordered as a footnote of the last page of the document
            return PadesVisualPositioningPresets::getFootnote(getRestPkiClient());

        case 2:
            // Example #2: get the footnote positioning preset and customize it
            $visualPosition = PadesVisualPositioningPresets::getFootnote(getRestPkiClient());
            $visualPosition->auto->container->left = 2.54;
            $visualPosition->auto->container->bottom = 2.54;
            $visualPosition->auto->container->right = 2.54;
            return $visualPosition;

        case 3:
            // Example #3: automatic positioning on new page. This will insert the signature, and future signatures,
            // in a new page appended to the end of the document.
            return PadesVisualPositioningPresets::getNewPage(getRestPkiClient());

        case 4:
            // Example #4: get the "new page" positioning preset and customize it
            $visualPosition = PadesVisualPositioningPresets::getNewPage(getRestPkiClient());
            $visualPosition->auto->container->left = 2.54;
            $visualPosition->auto->container->top = 2.54;
            $visualPosition->auto->container->right = 2.54;
            $visualPosition->auto->signatureRectangleSize->width = 5;
            $visualPosition->auto->signatureRectangleSize->height = 3;
            return $visualPosition;

        case 5:
            // Example #5: manual positioning
            return [
                'pageNumber' => 0, // zero means the signature will be placed on a new page appended to the end of the document
                'measurementUnits' => 'Centimeters',
                // define a manual position of 5cm x 3cm, positioned at 1 inch from the left and bottom margins
                'manual' => [
                    'left' => 2.54,
                    'bottom' => 2.54,
                    'width' => 5,
                    'height' => 3
                ]
            ];

        case 6:
            // Example #6: custom auto positioning
            return [
                'pageNumber' => -1, // negative values represent pages counted from the end of the document (-1 is last page)
                'measurementUnits' => 'Centimeters',
                'auto' => [
                    // Specification of the container where the signatures will be placed, one after the other
                    'container' => [
                        // Specifying left and right (but no width) results in a variable-width container with the given margins
                        'left' => 2.54,
                        'right' => 2.54,
                        // Specifying bottom and height (but no top) results in a bottom-aligned fixed-height container
                        'bottom' => 2.54,
                        'height' => 12.31
                    ],
                    // Specification of the size of each signature rectangle
                    'signatureRectangleSize' => [
                        'width' => 5,
                        'height' => 3
                    ],
                    // The signatures will be placed in the container side by side. If there's no room left, the signatures
                    // will "wrap" to the next row. The value below specifies the vertical distance between rows
                    'rowSpacing' => 1
                ]
            ];

        default:
            return null;
    }
}

// Get the document id for this signature (received by a post call, see batch-signature-form.js)
$id = $_POST['id'];

// Get an instance of the PadesSignatureStarter class, responsible for receiving the signature elements and start the
// signature process
$signatureStarter = new PadesSignatureStarter(getRestPkiClient());

// Set the document to be signed based on its ID
$signatureStarter->setPdfToSignPath("content/{$id}.pdf");

// Set the signature policy
$signatureStarter->setSignaturePolicy(StandardSignaturePolicies::PADES_BASIC);

// Set a SecurityContext to be used to determine trust in the certificate chain
$signatureStarter->setSecurityContext(StandardSecurityContexts::PKI_BRAZIL);
// Note: By changing the SecurityContext above you can accept only certificates from a certain PKI, for instance,
// ICP-Brasil (\Lacuna\StandardSecurityContexts::PKI_BRAZIL).

// Set the visual representation for the signature
$signatureStarter->setVisualRepresentation([

    'text' => [

        // The tags {{signerName}} and {{signerNationalId}} will be substituted according to the user's certificate
        // signerName -> full name of the signer
        // signerNationalId -> if the certificate is ICP-Brasil, contains the signer's CPF
        'text' => 'Signed by {{signerName}} ({{signerNationalId}})',

        // Specify that the signing time should also be rendered
        'includeSigningTime' => true,

        // Optionally set the horizontal alignment of the text ('Left' or 'Right'), if not set the default is Left
        'horizontalAlign' => 'Left',
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
        'horizontalAlign' => 'Right'

    ],

    // Position of the visual representation. We have encapsulated this code in a function to include several
    // possibilities depending on the argument passed to the function. Experiment changing the argument to see
    // different examples of signature positioning. Once you decide which is best for your case, you can place the
    // code directly here.
    'position' => getVisualRepresentationPosition(3)
]);

// Call the startWithWebPki() method, which initiates the signature. This yields the token, a 43-character
// case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
// signWithRestPki() method on the Web PKI component (see batch-signature-form.js) and also to complete the signature
// on the POST action below (this should not be mistaken with the API access token).
$token = $signatureStarter->startWithWebPki();

// Return a JSON with the token obtained from REST PKI (the page will use jQuery to decode this value)
echo json_encode($token);
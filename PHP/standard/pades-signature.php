<?php

/*
 * This file initiates a PAdES signature using REST PKI and renders the signature page. The form is posted to
 * another file, pades-signature-action.php, which calls REST PKI again to complete the signature.
 *
 * Both PAdES signature examples, with a server file and with a file uploaded by the user, use this file. The difference
 * is that, when the file is uploaded by the user, the page is called with a URL argument named "userfile".
 */

// The file RestPki.php contains the helper classes to call the REST PKI API
require_once 'RestPki.php';

// The file util.php contains the function getRestPkiClient(), which gives us an instance of the RestPkiClient class
// initialized with the API access token
require_once 'util.php';

// The file pades-visual-elements.php contains sample settings for visual representations and PDF marks (see below)
require_once 'pades-visual-elements.php';

use Lacuna\PadesSignatureStarter;
use Lacuna\StandardSignaturePolicies;
use Lacuna\PadesMeasurementUnits;
use Lacuna\PadesVisualElements;
use Lacuna\StandardSecurityContexts;

// Instantiate the PadesSignatureStarter class, responsible for receiving the signature elements and start the signature
// process
$signatureStarter = new PadesSignatureStarter(getRestPkiClient());

// Set the unit of measurement used to edit the pdf marks and visual representations
$signatureStarter->measurementUnits = PadesMeasurementUnits::CENTIMETERS;

// Set the signature policy
$signatureStarter->setSignaturePolicy(StandardSignaturePolicies::PADES_BASIC_WITH_ICPBR_CERTS);

// Alternative option: add a ICP-Brasil timestamp to the signature
//$signatureStarter->setSignaturePolicy(StandardSignaturePolicies::PADES_T_WITH_ICPBR_CERTS);

// Alternative option: PAdES Basic with PKIs trusted by Windows
//$signatureStarter->setSignaturePolicy(StandardSignaturePolicies::PADES_BASIC);
//$signatureStarter->setSecurityContext(StandardSecurityContexts::WINDOWS_SERVER);

// Alternative option: PAdES Basic with a custom security context containting, for instance, your private PKI certificate
//$signatureStarter->setSignaturePolicy(StandardSignaturePolicies::PADES_BASIC);
//$signatureStarter->setSecurityContext('ID OF YOUR CUSTOM SECURITY CONTEXT');

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
            'content' => base64_encode(getPdfStampContent()),
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
    // code directly here.
    'position' => PadesVisualElements::getVisualRepresentationPosition(1)

]);

// If the user was redirected here by upload.php (signature with file uploaded by user), the "userfile" URL argument
// will contain the filename under the "app-data" folder. Otherwise (signature with server file), we'll sign a sample
// document.
$userfile = isset($_GET['userfile']) ? $_GET['userfile'] : null;
if (!empty($userfile)) {
    $signatureStarter->setPdfToSignPath("app-data/{$userfile}");
} else {
    $signatureStarter->setPdfToSignPath('content/SampleDocument.pdf');
}

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
//array_push($signatureStarter->pdfMarks, PadesVisualElements::getPdfMark(1));

// Call the startWithWebPki() method, which initiates the signature. This yields the token, a 43-character
// case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
// signWithRestPki() method on the Web PKI component (see javascript below) and also to complete the signature after
// the form is submitted (see file pades-signature-action.php). This should not be mistaken with the API access token.
$token = $signatureStarter->startWithWebPki();

// The token acquired above can only be used for a single signature attempt. In order to retry the signature it is
// necessary to get a new token. This can be a problem if the user uses the back button of the browser, since the
// browser might show a cached page that we rendered previously, with a now stale token. To prevent this from happening,
// we call the function setExpiredPage(), located in util.php, which sets HTTP headers to prevent caching of the page.
setExpiredPage();

?><!DOCTYPE html>
<html>
<head>
    <title>PAdES Signature</title>
    <?php include 'includes.php' // jQuery and other libs (used only to provide a better user experience, but NOT required to use the Web PKI component) ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely ?>

<div class="container">

    <h2>PAdES Signature</h2>

    <?php // notice that we'll post to a different PHP file ?>
    <form id="signForm" action="pades-signature-action.php" method="POST">

        <?php // render the $token in a hidden input field ?>
        <input type="hidden" name="token" value="<?= $token ?>">

        <div class="form-group">
            <label>File to sign</label>
            <?php if (!empty($userfile)) { ?>
                <p>You'll are signing <a href='app-data/<?= $userfile ?>'>this document</a>.</p>
            <?php } else { ?>
                <p>You'll are signing <a href='content/SampleDocument.pdf'>this sample document</a>.</p>
            <?php } ?>
        </div>

        <?php
        // Render a select (combo box) to list the user's certificates. For now it will be empty, we'll populate it
        // later on (see javascript below).
        ?>
        <div class="form-group">
            <label for="certificateSelect">Choose a certificate</label>
            <select id="certificateSelect" class="form-control"></select>
        </div>

        <?php
        // Action buttons. Notice that the "Sign File" button is NOT a submit button. When the user clicks the button,
        // we must first use the Web PKI component to perform the client-side computation necessary and only when
        // that computation is finished we'll submit the form programmatically (see javascript below).
        ?>
        <button id="signButton" type="button" class="btn btn-primary">Sign File</button>
        <button id="refreshButton" type="button" class="btn btn-default">Refresh Certificates</button>
    </form>

</div>

<?php
// The file below contains the JS lib for accessing the Web PKI component. For more information, see:
// https://webpki.lacunasoftware.com/#/Documentation
?>
<script src="content/js/lacuna-web-pki-2.3.1.js"></script>

<?php
// The file below contains the logic for calling the Web PKI component. It is only an example, feel free to alter it
// to meet your application's needs. You can also bring the code into the javascript block below if you prefer.
?>
<script src="content/js/signature-form.js"></script>
<script>
    $(document).ready(function () {
        // Once the page is ready, we call the init() function on the javascript code (see signature-form.js)
        signatureForm.init({
            token: '<?= $token ?>',                     // token acquired from REST PKI
            form: $('#signForm'),                       // the form that should be submitted when the operation is complete
            certificateSelect: $('#certificateSelect'), // the select element (combo box) to list the certificates
            refreshButton: $('#refreshButton'),         // the "refresh" button
            signButton: $('#signButton')                // the button that initiates the operation
        });
    });
</script>

</body>
</html>

<?php

/*
 * This file initiates a PAdES signature using REST PKI and renders the signature page. The form is posted to
 * another file, pades-signature-action.php, which calls REST PKI again to complete the signature.
 *
 * Both PAdES signature examples, with a server file and with a file uploaded by the user, use this file. The difference
 * is that, when the file is uploaded by the user, the page is called with a URL argument named "userfile".
 */

require __DIR__ . '/vendor/autoload.php';

use Lacuna\RestPki\PadesSignatureStarter;
use Lacuna\RestPki\PadesVisualPositioningPresets;
use Lacuna\RestPki\StandardSignaturePolicies;
use Lacuna\RestPki\PadesMeasurementUnits;
use Lacuna\RestPki\StandardSecurityContexts;

// Instantiate the PadesSignatureStarter class, responsible for receiving the signature elements and start the signature
// process.
$signatureStarter = new PadesSignatureStarter(getRestPkiClient());

// If the user was redirected here by upload.php (signature with file uploaded by user), the "userfile" URL argument
// will contain the filename under the "app-data" folder. Otherwise (signature with server file), we'll sign a sample
// document.
$userfile = isset($_GET['userfile']) ? $_GET['userfile'] : null;
if (!empty($userfile)) {
    $signatureStarter->setPdfToSignFromPath("app-data/{$userfile}");
} else {
    $signatureStarter->setPdfToSignFromPath('content/SampleDocument.pdf');
}

// Set the signature policy.
$signatureStarter->signaturePolicy = StandardSignaturePolicies::PADES_BASIC;

// Set the security context. We have encapsulated the security context choice on util.php.
$signatureStarter->securityContext = getSecurityContextId();

// Set the unit of measurement used to edit the pdf marks and visual representations.
$signatureStarter->measurementUnits = PadesMeasurementUnits::CENTIMETERS;

// Set the visual representation to the signature. We have encapsulated this code (on util-pades.php) to be used on
// various PAdES examples.
$signatureStarter->visualRepresentation = getVisualRepresentation(getRestPkiClient());

/*
	Optionally, add marks to the PDF before signing. These differ from the signature visual representation in that
	they are actually changes done to the document prior to signing, not binded to any signature. Therefore, any number
	of marks can be added, for instance one per page, whereas there can only be one visual representation per signature.
	However, since the marks are in reality changes to the PDF, they can only be added to documents which have no
    previous signatures, otherwise such signatures would be made invalid by the changes to the document (see property
	PadesSignatureStarter::bypassMarksIfSigned). This problem does not occur with signature visual representations.

	We have encapsulated this code in a method to include several possibilities depending on the argument passed.
	Experiment changing the argument to see different examples of PDF marks. Once you decide which is best for your case,
	you can place the code directly here.
*/
//array_push($signatureStarter->pdfMarks, getPdfMark(1));

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
        // later on (see signature-form.js).
        ?>
        <div class="form-group">
            <label for="certificateSelect">Choose a certificate</label>
            <select id="certificateSelect" class="form-control"></select>
        </div>

        <?php
        // Action buttons. Notice that the "Sign File" button is NOT a submit button. When the user clicks the button,
        // we must first use the Web PKI component to perform the client-side computation necessary and only when
        // that computation is finished we'll submit the form programmatically (see signature-form.js).
        ?>
        <button id="signButton" type="button" class="btn btn-primary">Sign File</button>
        <button id="refreshButton" type="button" class="btn btn-default">Refresh Certificates</button>
    </form>

</div>

<?php
// The file below contains the JS lib for accessing the Web PKI component. For more information, see:
// https://webpki.lacunasoftware.com/#/Documentation
?>
<script src="content/js/lacuna-web-pki-2.9.0.js"></script>

<?php
// The file below contains the logic for calling the Web PKI component. It is only an example, feel free to alter it
// to meet your application's needs. You can also bring the code into the javascript block below if you prefer.
?>
<script src="content/js/signature-form.js"></script>
<script>
    $(document).ready(function () {
        // Once the page is ready, we call the init() function on the javascript code (see signature-form.js).
        signatureForm.init({
            token: '<?= $token ?>',                     // The token acquired from REST PKI.
            form: $('#signForm'),                       // The form that should be submitted when the operation is complete.
            certificateSelect: $('#certificateSelect'), // The <select> element (combo box) to list the certificates.
            refreshButton: $('#refreshButton'),         // The "refresh" button.
            signButton: $('#signButton')                // The button that initiates the operation.
        });
    });
</script>

</body>
</html>

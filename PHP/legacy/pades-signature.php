<?php

/*
 * This file initiates a PAdES signature using REST PKI and renders the signature page. The form is posted to
 * another file, pades-signature-action.php, which calls REST PKI again to complete the signature.
 *
 * Both PAdES signature examples, with a server file and with a file uploaded by the user, use this file. The difference
 * is that, when the file is uploaded by the user, the page is called with a URL argument named "userfile".
 */

// The file RestPkiLegacy.php contains the helper classes to call the REST PKI API for PHP 5.3+. Notice: if you're using
// PHP version 5.5 or greater, please use one of the other samples, which make better use of the extended capabilities
// of the newer versions of PHP - https://github.com/LacunaSoftware/RestPkiSamples/tree/master/PHP
require_once 'RestPkiLegacy.php';

// The file util.php contains the function getRestPkiClient(), which gives us an instance of the RestPkiClient class
// initialized with the API access token
require_once 'util.php';

// The file pades-visual-elements.php contains sample settings for visual representations and PDF marks (see below)
require_once 'pades-visual-elements.php';

use Lacuna\PadesSignatureStarter;
use Lacuna\PadesMeasurementUnits;
use Lacuna\PadesVisualElements;
use Lacuna\StandardSecurityContexts;
use Lacuna\StandardSignaturePolicies;

// Instantiate the PadesSignatureStarter class, responsible for receiving the signature elements and start the signature
// process
$signatureStarter = new PadesSignatureStarter(getRestPkiClient());

// Set the unit of measurement used to edit the pdf marks and visual representations
$signatureStarter->measurementUnits = PadesMeasurementUnits::CENTIMETERS;

// Set the signature policy
$signatureStarter->setSignaturePolicy(StandardSignaturePolicies::PADES_BASIC);

// Set a SecurityContext to be used to determine trust in the certificate chain
$signatureStarter->setSecurityContext(StandardSecurityContexts::PKI_BRAZIL);
// Note: By changing the SecurityContext above you can accept only certificates from a certain PKI, for instance,
// ICP-Brasil (\Lacuna\StandardSecurityContexts::PKI_BRAZIL).

// Set the visual representation for the signature
$signatureStarter->setVisualRepresentation(array(

	'text' => array(

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
		'container' => array(
			'left' => 0,
			'top' => 0,
			'right' => 1.5,
			'bottom' => 0
		)
	),

	'image' => array(

		// We'll use as background the image content/PdfStamp.png
		'resource' => array(
			'content' => base64_encode(getPdfStampContent()),
			'mimeType' => 'image/png'
		),

		// Opacity is an integer from 0 to 100 (0 is completely transparent, 100 is completely opaque).
		'opacity' => 50,

		// Align the image to the right
		'horizontalAlign' => 'Right',

		// Align the image to the center
		'verticalAlign' => 'Center'
	),

	// Position of the visual representation. We have encapsulated this code in a function to include several
	// possibilities depending on the argument passed to the function. Experiment changing the argument to see
	// different examples of signature positioning. Once you decide which is best for your case, you can place the
	// code directly here.
	'position' => PadesVisualElements::getVisualRepresentationPosition(3)

));

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
	However, since the marks are in reality changes to the PDF, they can only be added to documents which have no
	previous signatures, otherwise such signatures would be made invalid by the changes to the document (see property
	PadesSignatureStarter.BypassMarksIfSigned). This problem does not occurr with signature visual representations.
	We have encapsulated this code in a method to include several possibilities depending on the argument passed.
	Experiment changing the argument to see different examples of PDF marks. Once you decide which is best for your
	case, you can place the code directly here.
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
// we call the function setNoCacheHeaders() (util.php), which sets HTTP headers to prevent caching of the page.
setNoCacheHeaders();

?><!DOCTYPE html>
<html>
<head>
	<title>PAdES Signature</title>
	<?php include 'includes.php' // jQuery and other libs (for a sample without jQuery,
								 // see https://github.com/LacunaSoftware/RestPkiSamples/tree/master/PHP) ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely ?>

<div class="container">

	<h2>PAdES Signature</h2>

	<?php // notice that we'll post to a different PHP file ?>
	<form id="signForm" action="pades-signature-action.php" method="POST">

		<?php // render the $token in a hidden input field ?>
		<input type="hidden" name="token" value="<?php echo $token; ?>">

		<div class="form-group">
			<label>File to sign</label>
			<?php if (!empty($userfile)) { ?>
				<p>You'll are signing <a href='app-data/<?php echo $userfile; ?>'>this document</a>.</p>
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
			// Action buttons. Notice that the "Sign File" button is NOT a submit button. When the user clicks the
			// button, we must first use the Web PKI component to perform the client-side computation necessary and only
			// when that computation is finished we'll submit the form programmatically (see javascript below).
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
<script>

	var pki = new LacunaWebPKI();

	// -------------------------------------------------------------------------------------------------
	// Function called once the page is loaded
	// -------------------------------------------------------------------------------------------------
	function init() {

		// Wireup of button clicks
		$('#signButton').click(sign);
		$('#refreshButton').click(refresh);

		// Block the UI while we get things ready
		$.blockUI();

		// Call the init() method on the LacunaWebPKI object, passing a callback for when
		// the component is ready to be used and another to be called when an error occurs
		// on any of the subsequent operations. For more information, see:
		// https://webpki.lacunasoftware.com/#/Documentation#coding-the-first-lines
		// http://webpki.lacunasoftware.com/Help/classes/LacunaWebPKI.html#method_init
		pki.init({
			ready: loadCertificates, // as soon as the component is ready we'll load the certificates
			defaultError: onWebPkiError
		});
	}

	// -------------------------------------------------------------------------------------------------
	// Function called when the user clicks the "Refresh" button
	// -------------------------------------------------------------------------------------------------
	function refresh() {
		// Block the UI while we load the certificates
		$.blockUI();
		// Invoke the loading of the certificates
		loadCertificates();
	}

	// -------------------------------------------------------------------------------------------------
	// Function that loads the certificates, either on startup or when the user
	// clicks the "Refresh" button. At this point, the UI is already blocked.
	// -------------------------------------------------------------------------------------------------
	function loadCertificates() {

		// Call the listCertificates() method to list the user's certificates
		pki.listCertificates({

			// specify that expired certificates should be ignored
			filter: pki.filters.isWithinValidity,

			// in order to list only certificates within validity period and having a CPF (ICP-Brasil), use this instead:
			//filter: pki.filters.all(pki.filters.hasPkiBrazilCpf, pki.filters.isWithinValidity),

			// id of the select to be populated with the certificates
			selectId: 'certificateSelect',

			// function that will be called to get the text that should be displayed for each option
			selectOptionFormatter: function (cert) {
				return cert.subjectName + ' (issued by ' + cert.issuerName + ')';
			}

		}).success(function () {

			// once the certificates have been listed, unblock the UI
			$.unblockUI();

		});
	}

	// -------------------------------------------------------------------------------------------------
	// Function called when the user clicks the "Sign" button
	// -------------------------------------------------------------------------------------------------
	function sign() {

		// Block the UI while we perform the signature
		$.blockUI();

		// Get the thumbprint of the selected certificate
		var selectedCertThumbprint = $('#certificateSelect').val();

		// Call signWithRestPki() on the Web PKI component passing the token received from REST PKI and the certificate
		// selected by the user.
		pki.signWithRestPki({
			token: '<?php echo $token; ?>',
			thumbprint: selectedCertThumbprint
		}).success(function() {
			// Once the operation is completed, we submit the form
			$('#signForm').submit();
		});
	}

	// -------------------------------------------------------------------------------------------------
	// Function called if an error occurs on the Web PKI component
	// -------------------------------------------------------------------------------------------------
	function onWebPkiError(message, error, origin) {
		// Unblock the UI
		$.unblockUI();
		// Log the error to the browser console (for debugging purposes)
		if (console) {
			console.log('An error has occurred on the signature browser component: ' + message, error);
		}
		// Show the message to the user. You might want to substitute the alert below with a more user-friendly UI
		// component to show the error.
		alert(message);
	}

	// Schedule the init function to be called once the page is loaded
	$(document).ready(init);

</script>

</body>
</html>

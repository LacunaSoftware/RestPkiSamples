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

// Instantiate the PadesSignatureStarter class, responsible for receiving the signature elements and start the signature
// process
$signatureStarter = new PadesSignatureStarter(getRestPkiClient());

// If the user was redirected here by upload.php (signature with file uploaded by user), the "userfile" URL argument
// will contain the filename under the "app-data" folder. Otherwise (signature with server file), we'll sign a sample
// document.
$userfile = isset($_GET['userfile']) ? $_GET['userfile'] : null;
if (!empty($userfile)) {
	$signatureStarter->setPdfToSignPath("app-data/{$userfile}");
} else {
	$signatureStarter->setPdfToSignPath('content/SampleDocument.pdf');
}

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
		'horizontalAlign' => 'Left'

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

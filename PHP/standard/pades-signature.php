<?php

require_once 'RestPki.php';
require_once 'util.php';

use Lacuna\PadesSignatureStarter;
use Lacuna\PadesVisualPositioningPresets;
use Lacuna\StandardSecurityContexts;
use Lacuna\StandardSignaturePolicies;

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

$pdfPath = array_key_exists('file', $_GET) ? 'uploads/' . $_GET['file'] . '.pdf' : 'content/SampleDocument.pdf';

$signatureStarter = new PadesSignatureStarter(getRestPkiClient());
$signatureStarter->setPdfToSignPath($pdfPath);
$signatureStarter->setSignaturePolicy(StandardSignaturePolicies::PADES_BASIC);
$signatureStarter->setSecurityContext(StandardSecurityContexts::PKI_BRAZIL);
$signatureStarter->setVisualRepresentation([
	'text' => [
		'text' => 'Signed by {{signerName}} ({{signerNationalId}})',
		'includeSigningTime' => true
	],
	'image' => [
		'resource' => [
			'content' => base64_encode(file_get_contents('content/PdfStamp.png')),
			'mimeType' => 'image/png'
		],
		'opacity' => 50,
		'horizontalAlign' => 'Right'
	],
	'position' => getVisualRepresentationPosition(3)
]);

$token = $signatureStarter->startWithWebPki();
setExpiredPage();

?><!DOCTYPE html>
<html>
<head>
	<title>PAdES Signature</title>
	<?php include 'includes.php' ?>
</head>
<body>

<?php include 'menu.php' ?>

<div class="container">

	<h2>PAdES Signature</h2>

	<form id="signForm" action="pades-signature-action.php" method="POST">
		<input type="hidden" name="token" value="<?= $token ?>">
		<div class="form-group">
			<label>File to sign</label>
			<p>You'll be signing <a href='<?= $pdfPath ?>'>this document</a>.</p>
		</div>
		<div class="form-group">
			<label for="certificateSelect">Choose a certificate</label>
			<select id="certificateSelect" class="form-control"></select>
		</div>
		<button id="signButton" type="button" class="btn btn-primary">Sign File</button>
		<button id="refreshButton" type="button" class="btn btn-default">Refresh Certificates</button>
	</form>

</div>

<script src="content/js/lacuna-web-pki-2.2.2.js"></script>
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
			ready: loadCertificates,
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

		var select = $('#certificateSelect');

		// Clear the existing items on the dropdown
		select.find('option').remove();

		// Call listCertificates() on the LacunaWebPKI object. For more information see
		// http://webpki.lacunasoftware.com/Help/classes/LacunaWebPKI.html#method_listCertificates
		pki.listCertificates().success(function (certs) {

			// This anonymous function is called asynchronously once the listCertificates operation completes.
			// We'll populate the certificateSelect dropdown with the certificates, placing the
			// "thumbprint" property of each certificate on the value attribute of each item (this will be important later on).
			$.each(certs, function () {
				select.append(
					$('<option />')
						.val(this.thumbprint) // Don't change what is used as the value attribute
						.text(this.subjectName + ' (issued by ' + this.issuerName + ')') // You may customize here what is displayed for each item
				);
			});

			// Unblock the UI
			$.unblockUI();
		});
	}

	// -------------------------------------------------------------------------------------------------
	// Function called when the user clicks the "Sign" button
	// -------------------------------------------------------------------------------------------------
	function sign() {

		// Block the UI while we perform the signature
		$.blockUI();

		// Get the value attribute of the option selected on the dropdown. Since we placed the "thumbprint"
		// property on the value attribute of each item (see function loadCertificates above), we're actually
		// retrieving the thumbprint of the selected certificate.
		var selectedCertThumbprint = $('#certificateSelect').val();

		pki.signWithRestPki({
			thumbprint: selectedCertThumbprint,
			token: '<?= $token ?>'
		}).success(function() {
			$('#signForm').submit();
		});
	}

	// -------------------------------------------------------------------------------------------------
	// Function called if an error occurs on the Web PKI component
	// -------------------------------------------------------------------------------------------------
	function onWebPkiError(message, error, origin) {
		$.unblockUI();
		if (console) {
			console.log('An error has occurred on the signature browser component: ' + message, error);
		}
		alert(message);
	}

	// Schedule the init function to be called once the page is loaded
	$(document).ready(init);

</script>

</body>
</html>

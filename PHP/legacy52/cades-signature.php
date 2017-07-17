<?php

/*
 * This file initiates a CAdES signature using REST PKI and renders the signature page. The form is posted to
 * another file, cades-signature-action.php, which calls REST PKI again to complete the signature.
 *
 * All CAdES signature examples converge to this action, but with different URL arguments:
 *
 * 1. Signature with a server file               : no arguments filled
 * 2. Signature with a file uploaded by the user : "userfile" filled
 * 3. Co-signature of a previously signed CMS    : "cmsfile" filled
 */

// The file RestPkiLegacy52.php contains the helper classes to call the REST PKI API for PHP 5.2+. Notice: if you're
// using PHP version 5.3 or greater, please use one of the other samples, which make better use of the extended
// capabilities of the newer versions of PHP - https://github.com/LacunaSoftware/RestPkiSamples/tree/master/PHP
require_once 'RestPkiLegacy52.php';

// The file util.php contains the function getRestPkiClient(), which gives us an instance of the RestPkiClient
// class initialized with the API access token
require_once 'util.php';


$userfile = isset($_GET['userfile']) ? $_GET['userfile'] : null;
$cmsfile = isset($_GET['cmsfile']) ? $_GET['cmsfile'] : null;

// Instantiate the RestPkiCadesSignatureStarter class, responsible for receiving the signature elements and start the
// signature process
$signatureStarter = new RestPkiCadesSignatureStarter(getRestPkiClient());


if (!empty($userfile)) {

	// If the URL argument "userfile" is filled, it means the user was redirected here by the file upload.php (signature
	// with file uploaded by user). We'll set the path of the file to be signed, which was saved in the "app-data" folder
	// by upload.php
	$signatureStarter->setFileToSign("app-data/{$userfile}");

} else if (!empty($cmsfile)) {

	/*
	 * If the URL argument "cmsfile" is filled, the user has asked to co-sign a previously signed CMS. We'll set the
	 * path to the CMS to be co-signed, which was previously saved in the "app-data" folder by the file
	 * cades-signature-action.php. Note two important things:
	 *
	 * 1. The CMS to be co-signed must be set using the method "setCmsToSign" or "setCmsFileToSign", not the method
	 *    "setContentToSign" nor "setFileToSign".
	 *
	 * 2. Since we're creating CMSs with encapsulated content (see call to setEncapsulateContent below), we don't need
	 *    to set the content to be signed, REST PKI will get the content from the CMS being co-signed.
	 */
	$signatureStarter->setCmsFileToSign("app-data/{$cmsfile}");

} else {

	// If both userfile and cmsfile are null, this is the "signature with server file" case. We'll set the path to
	// the sample document.
	$signatureStarter->setFileToSign('content/SampleDocument.pdf');

}

// Set the signature policy
$signatureStarter->setSignaturePolicy(RestPkiStandardSignaturePolicies::CADES_ICPBR_ADR_BASICA);
// Optionally, set a SecurityContext to be used to determine trust in the certificate chain
//$signatureStarter->setSecurityContext(RestPkiStandardSecurityContexts::PKI_BRAZIL);

// Note: Depending on the signature policy chosen above, setting the security context may be mandatory (this is not
// the case for ICP-Brasil policies, which will automatically use the PKI_BRAZIL security context if none is passed)

// Optionally, set whether the content should be encapsulated in the resulting CMS. If this parameter is omitted,
// the following rules apply:
// - If no CmsToSign is given, the resulting CMS will include the content
// - If a CmsToCoSign is given, the resulting CMS will include the content if and only if the CmsToCoSign also includes
//   the content
$signatureStarter->setEncapsulateContent(true);

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
	<title>CAdES Signature</title>
	<?php include 'includes.php' // jQuery and other libs (for a sample without jQuery, see https://github.com/LacunaSoftware/RestPkiSamples/tree/master/PHP#barebones-sample) ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely ?>

<div class="container">

	<h2>CAdES Signature</h2>

	<?php // notice that we'll post to a different PHP file ?>
	<form id="signForm" action="cades-signature-action.php" method="POST">

		<?php // render the $token in a hidden input field ?>
		<input type="hidden" name="token" value="<?php echo $token; ?>">

		<div class="form-group">
			<label>File to sign</label>
			<?php if (!empty($userfile)) { ?>
				<p>You are signing <a href='app-data/<?php echo $userfile; ?>'>this document</a>.</p>
			<?php } elseif (!empty($cmsfile)) { ?>
				<p>You are co-signing <a href='app-data/<?php echo $cmsfile; ?>'>this CMS</a>.</p>
			<?php } else { ?>
				<p>You are signing <a href='content/SampleDocument.pdf'>this sample document</a>.</p>
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
<script src="content/js/lacuna-web-pki-2.5.0.js"></script>
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

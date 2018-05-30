<?php

/*
 * This file initiates a CAdES signature using REST PKI and renders the signature page. The form is posted to
 * another file, cades-signature-action.php, which calls REST PKI again to complete the signature.
 *
 * All CAdES signature examples converge to this action, but with different URL arguments:
 *
 *  1. Signature with a server file               : no arguments filled
 *  2. Signature with a file uploaded by the user : "userfile" filled
 *  3. Co-signature of a previously signed CMS    : "cmsfile" filled
 */

require __DIR__ . '/vendor/autoload.php';

use Lacuna\RestPki\Legacy\CadesSignatureStarter;
use Lacuna\RestPki\Legacy\StandardSignaturePolicies;

$userfile = isset($_GET['userfile']) ? $_GET['userfile'] : null;
$cmsfile = isset($_GET['cmsfile']) ? $_GET['cmsfile'] : null;

// Get an instance of the CadesSignatureStarter class, responsible for receiving the signature elements and start the
// signature process.
$signatureStarter = new CadesSignatureStarter(getRestPkiClient());

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
	 *  1. The CMS to be co-signed must be set using the method "setCmsToSign" or "setCmsFileToSign", not the method
	 *     "setContentToSign" nor "setFileToSign".
	 *
	 *  2. Since we're creating CMSs with encapsulated content (see call to setEncapsulateContent below), we don't need
	 *     to set the content to be signed, REST PKI will get the content from the CMS being co-signed.
	 */
	$signatureStarter->setCmsFileToSign("app-data/{$cmsfile}");

} else {

	// If both userfile and cmsfile are null, this is the "signature with server file" case. We'll set the path to
	// the sample document.
	$signatureStarter->setFileToSign('content/SampleDocument.pdf');

}

// Set the signature policy.
$signatureStarter->setSignaturePolicy(StandardSignaturePolicies::CADES_ICPBR_ADR_BASICA);

// Set a the security context to be used to determine trust in the certificate chain. We have encapsulated the security
// context choice on util.php.
$signatureStarter->setSecurityContext(getSecurityContextId());

// Optionally, set whether the content should be encapsulated in the resulting CMS. If this parameter is omitted,
// the following rules apply:
//
//  - If no CmsToCoSign is given, the resulting CMS will include the content.
//  - If a CmsToCoSign is given, the resulting CMS will include the content if and only if the CmsToCoSign also includes
//    the content.
//
$signatureStarter->setEncapsulateContent(true);

// Call the startWithWebPki() method, which initiates the signature. This yields the token, a 43-character
// case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
// signWithRestPki() method on the Web PKI component (see javascript below) and also to complete the signature after
// the form is submitted (see file pades-signature-action.php). This should not be mistaken with the API access token.
$token = $signatureStarter->startWithWebPki();

// The token acquired above can only be used for a single signature attempt. In order to retry the signature it is
// necessary to get a new token. This can be a problem if the user uses the back button of the browser, since the
// browser might show a cached page that we rendered previously, with a now stale token. To prevent this from happening,
// we call the function setNoCacheHeaders(), located in util.php, which sets HTTP headers to prevent caching of the
// page.
setNoCacheHeaders();

?>

<!DOCTYPE html>
<html>
<head>
	<title>CAdES Signature</title>
	<?php include 'includes.php' // jQuery and other libs (used only to provide a better user experience, but NOT required to use the Web PKI component). ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely ?>

<div class="container">

	<h2>CAdES Signature</h2>

	<?php // Notice that we'll post to a different PHP file ?>
	<form id="signForm" action="cades-signature-action.php" method="POST">

		<?php // Render the $token in a hidden input field ?>
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
// The file below contains the logic for calling the Web PKI component. It is only an example, feel free to alter it to
// meet your application's needs. You can also bring the code into the javascript block below if you prefer.
?>
<script src="content/js/signature-form.js"></script>
<script>
    $(document).ready(function () {
       // Once the page is ready, we call the init() function to the javascript code (signature-form.js).
       signatureForm.init({
           token: '<?php echo $token; ?>',             // The token acquired form REST PKI.
           form: $('#signForm'),                       // The form that should be submitted when the operation is complete.
           certificateSelect: $('#certificateSelect'), // The <select> element (combo box) to list the certificates.
           refreshButton: $('#refreshButton'),         // The "refresh" button.
           signButton: $('#signButton')                // The button that initiates the operation.
       });
    });
</script>

</body>
</html>

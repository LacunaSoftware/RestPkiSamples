<?php

/*
 * This file initiates an authentication with REST PKI and renders the authentication page. The form is posted to
 * another file, authentication-action.php, which calls REST PKI again to validate the data received.
 */

// The file RestPki.php contains the helper classes to call the REST PKI API
require_once 'RestPki.php';

// The file util.php contains the function getRestPkiClient(), which gives us an instance of the RestPkiClient class
// initialized with the API access token
require_once 'util.php';

// Get an instance of the Authentication class
$auth = getRestPkiClient()->getAuthentication();

// Call the startWithWebPki() method, which initiates the authentication. This yields the "token", a 22-character
// case-sensitive URL-safe string, which represents this authentication process. We'll use this value to call the
// signWithRestPki() method on the Web PKI component (see javascript below) and also to call the completeWithWebPki()
// method on the file authentication-action.php. This should not be mistaken with the API access token.
$token = $auth->startWithWebPki(\Lacuna\StandardSecurityContexts::PKI_BRAZIL);

// Note: By changing the SecurityContext above you can accept only certificates from a certain PKI,
// for instance, ICP-Brasil (\Lacuna\StandardSecurityContexts::PKI_BRAZIL).

// The token acquired above can only be used for a single authentication. In order to retry authenticating it is
// necessary to get a new token. This can be a problem if the user uses the back button of the browser, since the
// browser might show a cached page that we rendered previously, with a now stale token. To prevent this from happening,
// we call the function setExpiredPage(), located in util.php, which sets HTTP headers to prevent caching of the page.
setExpiredPage();

?><!DOCTYPE html>
<html>
<head>
	<title>Authentication</title>
	<?php include 'includes.php' // jQuery and other libs (used only to provide a better user experience, but NOT required to use the Web PKI component) ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely ?>

<div class="container">

	<h2>Authentication</h2>

	<?php // notice that we'll post to a different PHP file ?>
	<form id="authForm" action="authentication-action.php" method="POST">

		<?php // render the $token in a hidden input field ?>
		<input type="hidden" name="token" value="<?= $token ?>">

		<?php
			// Render a select (combo box) to list the user's certificates. For now it will be empty, we'll populate it
			// later on (see javascript below).
		?>
		<div class="form-group">
			<label for="certificateSelect">Choose a certificate</label>
			<select id="certificateSelect" class="form-control"></select>
		</div>

		<?php
			// Action buttons. Notice that the "Sign In" button is NOT a submit button. When the user clicks the button,
			// we must first use the Web PKI component to perform the client-side computation necessary and only when
			// that computation is finished we'll submit the form programmatically (see javascript below).
		?>
		<button id="signInButton" type="button" class="btn btn-primary">Sign In</button>
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
		$('#signInButton').click(signIn);
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
	// Function called when the user clicks the "Sign In" button
	// -------------------------------------------------------------------------------------------------
	function signIn() {

		// Block the UI while we process the authentication
		$.blockUI();

		// Get the thumbprint of the selected certificate
		var selectedCertThumbprint = $('#certificateSelect').val();

		// Call signWithRestPki() on the Web PKI component passing the token received from REST PKI and the certificate
		// selected by the user. Although we're making an authentication, at the lower level we're actually signing
		// a cryptographic nonce (a random number generated by the REST PKI service), hence the name of the method.
		pki.signWithRestPki({
			token: '<?= $token ?>',
			thumbprint: selectedCertThumbprint,
		}).success(function () {
			// Once the operation is completed, we submit the form
			$('#authForm').submit();
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

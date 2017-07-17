// -----------------------------------------------------------------------------------------------------
// This file contains logic for calling the Web PKI component. It is only an example, feel free to alter
// it to meet your application's needs.
// -----------------------------------------------------------------------------------------------------
var signatureForm = (function () {

	var pki = new LacunaWebPKI(_webPkiLicense);
	var token = null;
	var selectElement = null;
	var submitButtonElement = null;

	// -------------------------------------------------------------------------------------------------
	// Initializes the signature form
	// -------------------------------------------------------------------------------------------------
	function init(args) {

		token = args.token;
		selectElement = args.certificateSelect;
		submitButtonElement = args.submitButton;

		// Block the UI while we get things ready
		$.blockUI();

		// Call the init() method on the LacunaWebPKI object, passing a callback for when
		// the component is ready to be used and another to be called when an error occurs
		// on any of the subsequent operations. For more information, see:
		// https://webpki.lacunasoftware.com/#/Documentation#coding-the-first-lines
		// http://webpki.lacunasoftware.com/Help/classes/LacunaWebPKI.html#method_init
		pki.init({
			ready: loadCertificates, // as soon as the component is ready we'll load the certificates
			defaultError: onWebPkiError,
			restPkiUrl: _restPkiEndpoint
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
			selectId: selectElement.attr('id'),

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
		var selectedCertThumbprint = selectElement.val();

		// Call signWithRestPki() on the Web PKI component passing the token received from REST PKI and the certificate
		// selected by the user.
		pki.signWithRestPki({
			token: token,
			thumbprint: selectedCertThumbprint
		}).success(function () {
			// Once the operation is completed, we programatically click the hidden button on the signature page, which will trigger the postback
			submitButtonElement.click();
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

	return {
		init: init,
		refresh: refresh,
		sign: sign
	};

})();

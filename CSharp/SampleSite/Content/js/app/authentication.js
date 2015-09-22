/*
 * This javascript file contains the client-side logic for the authentication example. Other parts can be found at:
 * - HTML: Views/Home/Authentication.cshtml
 * - Server-side logic: Api/AuthenticationController
 *
 * This code uses the Lacuna Web PKI component to access the user's certificates. For more information, see
 * https://webpki.lacunasoftware.com/#/Documentation
 */

// -------------------------------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------------------------------
var pki = new LacunaWebPKI();
var selectedCertThumbprint = null;
var token = null;

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
	// the component is ready to be used and another to be called when an error occurrs
	// on any of the subsequent operations. For more information, see:
	// https://webpki.lacunasoftware.com/#/Documentation#coding-the-first-lines
	// http://webpki.lacunasoftware.com/Help/classes/LacunaWebPKI.html#method_init
	pki.init({
		ready: onWebPkiReady,
		defaultError: onWebPkiError // generic error callback on Content/js/app/site.js
	});
}

// -------------------------------------------------------------------------------------------------
// Function called once the Lacuna Web PKI component is ready to be used
// -------------------------------------------------------------------------------------------------
function onWebPkiReady() {
	// Invoke the loading of the certificates (note that the UI is already blocked)
	loadCertificates();
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
// Function called when the user clicks the "Sign In" button
// -------------------------------------------------------------------------------------------------
function signIn() {

	// Block the UI while we process the authentication
	$.blockUI();

	// Get the value attribute of the option selected on the dropdown. Since we placed the "thumbprint"
	// property on the value attribute of each item (see function loadCertificates above), we're actually
	// retrieving the thumbprint of the selected certificate.
	selectedCertThumbprint = $('#certificateSelect').val();

	if (token) {
	    onAuthStarted();
	} else {
	    // Call the server to initiate the authentication (for more information see method AuthenticationController.Get())
	    $.ajax({
	        method: 'GET',
	        url: '/Api/Authentication',
	        success: function (response) {
	            token = response;
	            onAuthStarted();
	        },
	        error: onServerError // generic error callback on Content/js/app/site.js
	    });
	}
}

// -------------------------------------------------------------------------------------------------
// Function called when the server responds with a cryptographic nonce
// -------------------------------------------------------------------------------------------------
function onAuthStarted() {
    // Call signWithRestPki() on the LacunaWebPKI object to sign the nonce using the user's certificate
    pki.signWithRestPki({
        token: token,
        thumbprint: selectedCertThumbprint, // selected certificate's thumbprint
    }).success(onSignatureCompleted); // callback for when the operation completes
}

// -------------------------------------------------------------------------------------------------
// Function called once the signature of the nonce is completed
// -------------------------------------------------------------------------------------------------
function onSignatureCompleted() {

	// Call the server to complete the authentication (for more information see method AuthenticationController.Post())
	$.ajax({
		method: 'POST',
		url: '/Api/Authentication/' + token,
		contentType: 'application/json',
		success: onSignInProcessed,
		error: onServerError // generic error callback on Content/js/app/site.js
	});
	token = null;

	// Note on the encodings: the Web PKI component returns the certificate encoding in Base64, as well as the
	// signature result. The server-side models expect byte arrays. The ASP.NET Web API framework does the conversion
	// for us.
}

// -------------------------------------------------------------------------------------------------
// Function called once the server replies with the result of the authentication
// -------------------------------------------------------------------------------------------------
function onSignInProcessed(data, textStatus, jqXHR) {

	if (data.success) {

		// If success, the server already registered the user as signed in, let's redirect him
		// to the home, now signed in (note that the UI is still blocked, we'll redirect without
		// unblocking, that's OK)
		document.location.href = '/';

	} else {

		// If the authentication failed, we'll alert the user and unblock the UI so that
		// he can try again with another certificate.

		// Unblock the UI
		$.unblockUI();

		// Render the failure message
		addAlert('danger', data.message); // the addAlert function is located on the file Content/js/app/site.js

		// If the response includes a validationResults, display it
		if (data.validationResults) {
			var panel = $('#validationResultsPanel');
			panel.find('textarea').val(data.validationResults);
			panel.show();
		}

	}
}

// Schedule the init function to be called once the page is loaded
$(document).ready(init);

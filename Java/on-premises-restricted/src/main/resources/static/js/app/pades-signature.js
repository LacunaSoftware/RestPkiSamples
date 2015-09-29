/*
 * This javascript file contains the client-side logic for the PAdES signature example. Other parts can be found at:
 * - View: src/main/resources/templates/pades-signature.html
 * - Server-side logic: src/main/java/sample/controller/PadesSignatureController
 *
 * This code uses the Lacuna Web PKI component to access the user's certificates. For more information, see
 * https://webpki.lacunasoftware.com/#/Documentation
 */

// -------------------------------------------------------------------------------------------------
// Global variables
// -------------------------------------------------------------------------------------------------

// >>> NOTE: This sample will only work on localhost (any port is fine). In order to run this sample online, you'll
// need a license for the Web PKI component (set it below if you have one).
var webPkiLicense = null;

var pki = new LacunaWebPKI(webPkiLicense);
var selectedCertThumbprint = null;
var token = null;

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
	// the component is ready to be used and another to be called when an error occurrs
	// on any of the subsequent operations. For more information, see:
	// https://webpki.lacunasoftware.com/#/Documentation#coding-the-first-lines
	// http://webpki.lacunasoftware.com/Help/classes/LacunaWebPKI.html#method_init
	pki.init({
		ready: onWebPkiReady,
		defaultError: onWebPkiError // generic error callback on src/main/resources/static/js/app/site.js
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
// Function called when the user clicks the "Sign" button
// -------------------------------------------------------------------------------------------------
function sign() {

	// Block the UI while we perform the signature
	$.blockUI();

	// Get the value attribute of the option selected on the dropdown. Since we placed the "thumbprint"
	// property on the value attribute of each item (see function loadCertificates above), we're actually
	// retrieving the thumbprint of the selected certificate.
	selectedCertThumbprint = $('#certificateSelect').val();

	// Call readCertificate() on the Web PKI component passing the selected certificate's thumbprint. This
	// reads the certificate's encoding, which we'll need later on. For more information, see
	// http://webpki.lacunasoftware.com/Help/classes/LacunaWebPKI.html#method_readCertificate
	pki.readCertificate(selectedCertThumbprint).success(onCertificateRetrieved);
}

// -------------------------------------------------------------------------------------------------
// Function called once the user's certificate encoding has been read
// -------------------------------------------------------------------------------------------------
function onCertificateRetrieved(cert) {
	// Call the server to initiate the signature (for more information see method
	// PadesSignatureController.start()). The server will return the parameters needed to perform the signature.
	$.ajax({
		method: 'POST',
		url: '/api/pades-signature/start',
		data: JSON.stringify({
			certificate: cert // the server needs the user's certificate on this step
		}),
		contentType: 'application/json',
		success: onSignatureStarted,
		error: onServerError // generic error callback on Content/js/app/site.js
	});

	// Note on the encodings: the method PadesSignatureController.start() expects the parameter certificate as a byte
	// array encoded as a Base64 string, which is precisely what the readCertificate method yields.
}

// -------------------------------------------------------------------------------------------------
// Function called once the server replies with the parameters for the client-side signature
// -------------------------------------------------------------------------------------------------
function onSignatureStarted(data) {

	if (!data.success) {
		// Pre-validation of the signature parameters failed (there's probably something wrong
		// with the selected certificate, for instance it might be expired). We call our
		// onServerOperationFailed which displays the error.
		onServerOperationFailed(data);
		return;
	}

	// Store the "token" in a global variable (we'll need it later)
	token = data.token;

    // Call the signHash() method to perform the signature using the parameters returned by REST PKI
	pki.signHash({
		thumbprint: selectedCertThumbprint,
		hash: data.toSignHash,
		digestAlgorithm: data.digestAlgorithmOid
	}).success(onSignatureCompleted); // callback for when the operation completes
}

// -------------------------------------------------------------------------------------------------
// Function called once the signature is completed
// -------------------------------------------------------------------------------------------------
function onSignatureCompleted(signature) {
	// Call the server to complete the signature (for more information see method PadesSignatureController.complete())
	$.ajax({
		method: 'POST',
		url: '/api/pades-signature/complete',
		data: JSON.stringify({
			token: token,         // the token initially returned by the server, which we stored in a global variable
			signature: signature  // the result of the signature algorithm
		}),
		contentType: 'application/json',
		success: onProcessCompleted, // success callback
		error: onServerError // generic error callback on Content/js/app/site.js
	});

	// Note on the encodings: the method PadesSignatureController.complete() expects the parameter signature as a
	// byte arrays encoded as a Base64 string, which is precisely what the Web PKI component yields.
}

// -------------------------------------------------------------------------------------------------
// Function called once the server replies with the result of the signature
// -------------------------------------------------------------------------------------------------
function onProcessCompleted(data, textStatus, jqXHR) {

	if (!data.success) {
		// The signature could not be completed successfully, probably because there's something
		// wrong with the selected certificate, for instance it might be revoked. We call our 
		// onServerOperationFailed which displays the error.
		onServerOperationFailed(data);
		return;
	}

	// The signature was successful. Unblock the UI, inform the user and render a link to download the signature
	$.unblockUI();
	addAlert('success', 'File signed successfully! <a href="/Signature/Download/' + data.signatureId + '">click here</a> to download the signed file');
}

// -------------------------------------------------------------------------------------------------
// Function called when the server responds with a 200 (OK) but with success = false, usually in the
// case of a validation error
// -------------------------------------------------------------------------------------------------
function onServerOperationFailed(data) {

	// Unblock the UI
	$.unblockUI();

	// Render the failure message
	addAlert('danger', data.message);  // the addAlert function is located on the file src/main/resources/static/js/app/site.js

	// If the response includes a validationResults, display it
	if (data.validationResults) {
		var panel = $('#validationResultsPanel');
		panel.find('textarea').val(data.validationResults);
		panel.show();
	}
}

// Schedule the init function to be called once the page is loaded
$(document).ready(init);


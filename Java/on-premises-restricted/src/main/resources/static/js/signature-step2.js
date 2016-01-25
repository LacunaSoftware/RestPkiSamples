/*
 * This file contains the necessary calls to the Web PKI component to perform the final step in the signature process,
 * which consists in performing the actual digital signature.
 *
 * Once the signature is done, we'll place it in a hidden input field on the page's form and submit the form.
 *
 * This logic is shared by the PAdES, CAdES and XML signatures.
 */

// Get an instance of the LacunaWebPKI object
var pki = new LacunaWebPKI();

// -------------------------------------------------------------------------------------------------
// Function called once the page is loaded
// -------------------------------------------------------------------------------------------------
function init() {

    // Block the UI. We'll not unblock the UI (unless there's an error), since no user input is needed and
    // we'll submit the form at the end of the computations.
    $.blockUI();

    // Call the init() method on the LacunaWebPKI object, passing a callback for when
    // the component is ready to be used and another to be called when an error occurs
    // on any of the subsequent operations. For more information, see:
    // https://webpki.lacunasoftware.com/#/Documentation#coding-the-first-lines
    // http://webpki.lacunasoftware.com/Help/classes/LacunaWebPKI.html#method_init
    pki.init({
        ready: sign, // as soon as the component is ready we'll perform the signature
        defaultError: onWebPkiError
    });
}

// -------------------------------------------------------------------------------------------------
// Function called when the component is ready (note that the UI is already blocked)
// -------------------------------------------------------------------------------------------------
function sign() {
	// Call signHash() on the Web PKI component using the parameters given by REST PKI
	pki.signHash({
      thumbprint: $('#selectedCertThumbInput').val(),
      hash: $('#toSignHashInput').val(),
      digestAlgorithm: $('#digestAlgInput').val()
   }).success(function (signature) {
      // Place the signature in a hidden input field on the page's form
      $('#signatureInput').val(signature);
      // Submit the form (the UI will remain blocked)
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

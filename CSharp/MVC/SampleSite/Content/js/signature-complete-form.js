// ----------------------------------------------------------------------------------------------------------
// This file contains logic for calling the Web PKI component to perform the finalization of the signature
// process. It is only an example, feel free to alter it to meet your application's needs.
// ----------------------------------------------------------------------------------------------------------
var signatureCompleteForm = (function () {

    // Auxiliary global variable.
    var formElements = null;

    // Create an instance of the LacunaWebPKI object.
    var pki = new LacunaWebPKI(_webPkiLicense);

    // ------------------------------------------------------------------------------------------------------
    // Initializes the signature form.
    // ------------------------------------------------------------------------------------------------------
    function init(fe) {

        // Receive form parameters received as arguments.
        formElements = fe;

        // Verify if the form is invalid (this only happens after a unsuccessful form submission).
        if (!formElements.formIsValid) {
            formElements.tryAgainButton.show();
            return;
        }

        // Block the UI while we get things ready.
        $.blockUI({ message: 'Signing ...' });

        // Call the init() method on the LacunaWebPKI object, passing a callback for when the component is
        // ready to be used and another to be called when an error occurs on any of the subsequent
        // operations. For more information, see:
        // https://docs.lacunasoftware.com/en-us/articles/web-pki/get-started.html#coding-the-first-lines
        // https://webpki.lacunasoftware.com/Help/classes/LacunaWebPKI.html#method_init
        pki.init({
            ready: sign,                    // As soon as the component is ready we'll perform the signature.
            defaultError: onWebPkiError     // Generic error callback defined below.
        });
    }

    // ------------------------------------------------------------------------------------------------------
    // Function that performs the signature on startup. At this point, the UI is already blocked.
    // ------------------------------------------------------------------------------------------------------
    function sign() {

        // Call signHash() on the Web PKI component passing the "toSignHash", the digest algorithm and the
        // certificate selected by the user.
        pki.signHash({
            thumbprint: formElements.certThumbField.val(),
            hash: formElements.toSignHashField.val(),
            digestAlgorithm: formElements.digestAlgorithmOidField.val()
        }).success(function (signature) {
            // Fill the "signature" field, needed on server-side to complete the signature.
            formElements.signatureField.val(signature);
            // Submit the form.
            formElements.form.submit();
        });
    }

    // -------------------------------------------------------------------------------------------------
    // Function called if an error occurs on the Web PKI component.
    // -------------------------------------------------------------------------------------------------
    function onWebPkiError(message, error, origin) {

        // Unblock the UI.
        $.unblockUI();

        // Log the error to the browser console (for debugging purposes).
        if (console) {
            console.log('An error has occurred on the signature browser component: ' + message, error);
        }

        // Show the message to the user. You might want to substitute the alert below with a more
        // user-friendly UI component to show the error.
        addAlert('danger', 'An error has occurred on the signature browser component: ' + message);

        formElements.tryAgainButton.show();
    }

    return {
        init: init
    };

})();
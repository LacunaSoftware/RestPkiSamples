// -----------------------------------------------------------------------------------------------------
// This file contains logic for calling the Web PKI component. It is only an example, feel free to alter
// it to meet your application's needs.
// -----------------------------------------------------------------------------------------------------
var signatureWithoutIntegrationForm = (function() {

    var pki = null;
    var formElements = {};

    // -------------------------------------------------------------------------------------------------
    // Initializes the signature form.
    // -------------------------------------------------------------------------------------------------
    function init(fe) {

        // Receive form parameters received as arguments.
        formElements = fe;

        // Instance Web PKI object.
        pki = new LacunaWebPKI();

        if (formElements.stateField.val() == 'initial') {

            // Wireup of button clicks.
            formElements.signButton.click(startSignature);
            formElements.refreshButton.click(refresh);

            // Block the UI while we get things ready.
            $.blockUI({ message: 'Initializing ...' });

            // Call the init() method on the LacunaWebPKI object, passing a callback for when the
            // component is ready to be used and another to be called when an error occurs on any of the
            // subsequent operations. For more information, see:
            // https://docs.lacunasoftware.com/en-us/articles/web-pki/get-started.html#coding-the-first-lines
            // https://webpki.lacunasoftware.com/Help/classes/LacunaWebPKI.html#method_init
            pki.init({
                ready: loadCertificates, // As soon as the component is ready we'll load the certificates.
                defaultError: onWebPkiError
            });

        } else if (formElements.stateField.val() == 'start') {

            // Block the UI while we get things ready.
            $.blockUI({ message: 'Signing ...' });
            pki.init({
                ready: sign, // As soon as the component is ready we'll perform the signature.
                defaultError: onWebPkiError
            });

        }
    }

    // -------------------------------------------------------------------------------------------------
    // Function called when the user clicks the "Refresh" button
    // -------------------------------------------------------------------------------------------------
    function refresh() {
        // Block the UI while we load the certificates.
        $.blockUI({ message: 'Refreshing ...' });
        // Invoke the loading of the certificates.
        loadCertificates();
    }

    // -------------------------------------------------------------------------------------------------
    // Function that loads the certificates, either on startup or when the user clicks the "Refresh"
    // button. At this point, the UI is already blocked.
    // -------------------------------------------------------------------------------------------------
    function loadCertificates() {

        // Call the listCertificates() method to list the user's certificates.
        pki.listCertificates({

            // Specify that expired certificates should be ignored.
            filter: pki.filters.isWithinValidity,

            // In order to list only certificates within validity period and having a CPF (ICP-Brasil),
            // use this instead:
            //filter: pki.filters.all(pki.filters.hasPkiBrazilCpf, pki.filters.isWithinValidity),

            // Id of the select to be populated with the certificates.
            selectId: formElements.certificateSelect.attr('id'),

            // Function that will be called to get the text that should be displayed for each option.
            selectOptionFormatter: function (cert) {
                return cert.subjectName + ' (issued by ' + cert.issuerName + ')';
            }

        }).success(function () {

            // Once the certificates have been listed, unblock the UI.
            $.unblockUI();
        });

    }

    // -------------------------------------------------------------------------------------------------
    // Function called when the user clicks the "Sign File" button.
    // -------------------------------------------------------------------------------------------------
    function startSignature() {

        // Block the UI while we perform the signature
        $.blockUI({ message: 'Starting signature ...' });

        // Get the thumbprint of the selected certificate.
        var selectedCertThumbprint = formElements.certificateSelect.val();
        formElements.certThumbField.val(selectedCertThumbprint);

        // Get certificate content to be passed to "start" step of the signature.
        pki.readCertificate(selectedCertThumbprint).success(function (certEncoded) {

            // Submit form with "start" state.
            formElements.stateField.val('start');
            formElements.certContentField.val(certEncoded);
            formElements.form.submit();

        });
    }

    // -------------------------------------------------------------------------------------------------
    // Function called when the page is rendered on the "start" state. This will happen after the
    // "start" step on server-side (see method init() above).
    // -------------------------------------------------------------------------------------------------
    function sign() {

        // Call signHash() on the Web PKI component passing the "to-sign-hash", the digest algorithm and
        // the certificate selected by the user.
        pki.signHash({
            thumbprint: formElements.certThumbField.val(),
            hash: formElements.toSignHashField.val(),
            digestAlgorithm: formElements.digestAlgorithmOidField.val()
        }).success(function (signature) {

            // Submit form with "complete" state
            formElements.stateField.val('complete');
            formElements.signatureField.val(signature);
            formElements.form.submit();

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

        // Show the message to the user. You might want to substitute the alert below with a more
        // user-friendly UI component to show the error.
        alert(message);

        // Redirect to the same page discarding POST params.
        window.location = window.location;
    }

    return {
        init: init
    };

})();

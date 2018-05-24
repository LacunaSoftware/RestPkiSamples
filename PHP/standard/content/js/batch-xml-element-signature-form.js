// -----------------------------------------------------------------------------------------------------
// This file contains logic for calling the Web PKI component to sign a batch of documents. It is only
// an example, feel free to alter it to meet your application's needs.
// -----------------------------------------------------------------------------------------------------
var batchXmlElementSignatureForm = (function() {

    // Auxiliary global variables.
    var selectedCertThumbprint = null;
    var batchElemIds = null;
    var errors = 0;

    // Create an instance of the LacunaWebPKI object.
    var pki = new LacunaWebPKI(_webPkiLicense);

    // -------------------------------------------------------------------------------------------------
    // Function called once the page is loaded.
    // -------------------------------------------------------------------------------------------------
    function init(args) {

        // Receive the documents IDs.
        batchElemIds = args.elementsIds;

        // Wireup of button clicks.
        args.signButton.click(sign);
        args.refreshButton.click(refresh);

        // Block the UI while we get things ready.
        $.blockUI({ message: "Initializing ..." });

        // Call the init() method on the LacunaWebPKI object, passing a callback for when the component
        // is ready to be used and another to be called when an error occurs on any of the subsequent
        // operations. For more information, see:
        // http://docs.lacunasoftware.com/en-us/articles/web-pki/get-started.html
        // http://webpki.lacunasoftware.com/Help/classes/LacunaWebPKI.html#method_init
        pki.init({
            ready: loadCertificates, // As soon as the component is ready we'll load the certificates.
            defaultError: onWebPkiError, // Generic error callback.
            restPkiUrl: _restPkiEndpoint
        });
    }

    // -------------------------------------------------------------------------------------------------
    // Function called when the user clicks the "Refresh" button.
    // -------------------------------------------------------------------------------------------------
    function refresh() {
        // Block the UI while we load the certificates.
        $.blockUI({ message: "Initializing ..." });
        // Invoke the loading of the certificates.
        loadCertificates();
    }

    // -------------------------------------------------------------------------------------------------
    // Function that loads the certificates, either on startup or when the user clicks the "Refresh"
    // button. At this point, the UI is already blocked.
    // -------------------------------------------------------------------------------------------------
    function loadCertificates() {

        // Call the listCertificates() method to list the user's certificates. For more information see:
        // http://webpki.lacunasoftware.com/Help/classes/LacunaWebPKI.html#method_listCertificates
        pki.listCertificates({

            // The ID of the <select> element to be populated with the certificates.
            selectId: 'certificateSelect',

            // Function that will be called to get the text that should be displayed for each option.
            selectOptionFormatter: function (cert) {
                var s = cert.subjectName + ' (issued by ' + cert.issuerName + ')';
                if (new Date() > cert.validityEnd) {
                    s = '[EXPIRED] ' + s;
                }
                return s;
            }

        }).success(function () {

            // Once the certificates have been listed, unblock the UI.
            $.unblockUI();

        });
    }

    // -------------------------------------------------------------------------------------------------
    // Function called when the user clicks the "Sign Batch" button.
    // -------------------------------------------------------------------------------------------------
    function sign() {

        $("#messagesPanel").text('');

        // Block the UI while we perform the signature.
        $.blockUI({ message: "Signing (<span id='signNum'>1</span>/" + batchElemIds.length + ") ..." });

        // Get the thumbprint of the selected certificate and store it in a global variable (we'll need
        // it later).
        selectedCertThumbprint = $('#certificateSelect').val();

        // Call Web PKI to preauthorize the signatures, so that the user only sees one confirmation
        // dialog.
        pki.preauthorizeSignatures({
            certificateThumbprint: selectedCertThumbprint,
            signatureCount: batchElemIds.length // Number of signatures to be authorized by the user.
        }).success(startBatch); // Callback to be called if the user authorizes the signatures.
    }

    // -------------------------------------------------------------------------------------------------
    // Function called when the user authorizes the signatures.
    // -------------------------------------------------------------------------------------------------
    function startBatch() {
        startSignature({ elemId: 0 });
    }

    function startSignature(step) {

        // Verify if there more elements to be signed.
        if (step.elemId >= batchElemIds.length) {
            onBatchCompleted(step.fileId);
            return;
        }

        // Update signature number.
        $('#signNum').text(step.elemId + 1);

        $.ajax({
            url: 'batch-xml-element-signature-start.php',
            method: 'POST',
            data: {
                elemId: batchElemIds[step.elemId],
                fileId: step.fileId
            },
            dataType: 'json',
            success: function (token) {
                step.token = token;
                performSignature(step);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                // Render error and stop signature.
                addAlert('danger', 'An error has occurred while signing element\'s id \"' + batchElemIds[step.elemId] + '\": ' + (errorThrown || textStatus));
                $.unblockUI();
            }
        });
    }

    function performSignature(step) {
        // Call signWithRestPki() on the Web PKI component passing the token received from REST PKI and
        // the certificate selected by the user.
        pki.signWithRestPki({
            token: step.token,
            thumbprint: selectedCertThumbprint
        }).success(function () {
            // Complete signature with REST PKI.
            completeSignature(step);
        }).error(function (error) {
            // Render error and stop signature.
            addAlert('danger', 'An error has occurred while signing element\'s id \"' + batchElemIds[step.elemId] + '\": ' + error);
            $.unblockUI();
        });
    }

    function completeSignature(step) {

        $.ajax({
            url: 'batch-xml-element-signature-complete.php',
            method: 'POST',
            data: {
                token: step.token,
                fileId: step.fileId
            },
            dataType: 'json',
            success: function (fileId) {
                step.fileId = fileId;
                // Start the new signature.
                step.elemId++;
                startSignature({ elemId: step.elemId, fileId: fileId});
            },
            error: function (jqXHR, textStatus, errorThrown) {
                // Render error and stop signature.
                addAlert('danger', 'An error has occurred while signing element\'s id \"' + batchElemIds[step.elemId] + '\": ' + (errorThrown || textStatus));
                $.unblockUI();
            }
        });
    }

    // -------------------------------------------------------------------------------------------------
    // Function called once the batch is completed.
    // -------------------------------------------------------------------------------------------------
    function onBatchCompleted(filename) {

        // Unblock the UI.
        $.unblockUI();

        // Prevent user from clicking "sign batch" again (our logic isn't prepared for that).
        $('#signButton').prop('disabled', true);

        // Show signature results.
        $('#signedFileLink').attr('href', 'app-data/' + filename);
        $('#openSignatureLink').attr('href', 'open-xml-signature.php?userfile=' + filename);
        $('#signatureResult').show();
        $('#signForm').hide();
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
        alert(message);
    }

    // -------------------------------------------------------------------------------------------------
    // Function called to notify the user with some message.
    // -------------------------------------------------------------------------------------------------
    function addAlert(type, message) {
        $('#messagesPanel').append(
            '<div class="alert alert-' + type + ' alert-dismissible">' +
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
            '<span>' + message + '</span>' +
            '</div>');
    }

    return {
        init: init
    };

})();
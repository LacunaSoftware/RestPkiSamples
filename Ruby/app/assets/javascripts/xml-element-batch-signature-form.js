// -----------------------------------------------------------------------------------------------------
// This file contains logic for calling the Web PKI component to sign a batch of documents. It is only
// an example, feel free to alter it to meet your application's needs.
// -----------------------------------------------------------------------------------------------------
var batchXmlElementSignatureForm = (function() {

    // Auxiliary global variables
    var selectedCertThumbprint = null;
    var certificateSelect = null;
    var batchElemIds = null;
    var errorPanel = null;

    // Create an instance of the LacunaWebPKI object
    var pki = new LacunaWebPKI();

    // -------------------------------------------------------------------------------------------------
    // Function called once the page is loaded
    // -------------------------------------------------------------------------------------------------
    function init(args) {

        // Receive the documents ids
        batchElemIds = args.elementsIds;

        // Receive the error panel reference to show if occurred some error
        errorPanel = args.errorPanel;

        // Receive the certificate select reference
        certificateSelect = args.certificateSelect;

        // Wireup of button clicks
        args.signButton.click(sign);
        args.refreshButton.click(refresh);

        // Block the UI while we get things ready
        $.blockUI({ message: "Initializing ..." });

        // Call the init() method on the LacunaWebPKI object, passing a callback for when
        // the component is ready to be used and another to be called when an error occurrs
        // on any of the subsequent operations. For more information, see:
        // https://webpki.lacunasoftware.com/#/Documentation#coding-the-first-lines
        // http://webpki.lacunasoftware.com/Help/classes/LacunaWebPKI.html#method_init
        pki.init({
            ready: loadCertificates, // as soon as the component is ready we'll load the certificates
            defaultError: onWebPkiError // generic error callback
        });
    }

    // -------------------------------------------------------------------------------------------------
    // Function called when the user clicks the "Refresh" button
    // -------------------------------------------------------------------------------------------------
    function refresh() {
        // Block the UI while we load the certificates
        $.blockUI({ message: "Initializing ..." });
        // Invoke the loading of the certificates
        loadCertificates();
    }

    // -------------------------------------------------------------------------------------------------
    // Function that loads the certificates, either on startup or when the user
    // clicks the "Refresh" button. At this point, the UI is already blocked.
    // -------------------------------------------------------------------------------------------------
    function loadCertificates() {

        // Call the listCertificates() method to list the user's certificates. For more information see
        // http://webpki.lacunasoftware.com/Help/classes/LacunaWebPKI.html#method_listCertificates
        pki.listCertificates({

            // specify that expired certificates should be ignored
            filter: pki.filters.isWithinValidity,

            // in order to list only certificates within validity period and having a CPF (ICP-Brasil),
            // use this instead:
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
    // Function called when the user clicks the "Sign Batch" button
    // -------------------------------------------------------------------------------------------------
    function sign() {

        // Clear the error panel, if the some error has been occurred before
        errorPanel.text('');

        // Block the UI while we perform the signature (set the number, that indicates the current
        // signature, it will be updated using jQuery)
        $.blockUI({ message: "Signing (<span id='signNum'>1</span>/" + batchElemIds.length + ") ..." });

        // Get the thumbprint of the selected certificate and store it in a global variable (we'll need
        // it later)
        selectedCertThumbprint = certificateSelect.val();

        // Call Web PKI to preauthorize the signatures, so that the user only sees one confirmation
        // dialog
        pki.preauthorizeSignatures({
            certificateThumbprint: selectedCertThumbprint,
            signatureCount: batchElemIds.length // number of signatures to be authorized by the user
        }).success(startBatch); // callback to be called if the user authorizes the signatures
    }

    // -------------------------------------------------------------------------------------------------
    // Function called when the user authorizes the signatures
    // -------------------------------------------------------------------------------------------------
    function startBatch() {

        // Start the signature indicating the first element id
        startSignature({ elemId: 0 });
    }

    // -------------------------------------------------------------------------------------------------
    // Function that performs the first step of the signature process, which is the call the
    // batch-xml-signature/start POST routep in order to start the signature and get the token associated
    // with the signature process.
    // -------------------------------------------------------------------------------------------------
    function startSignature(step) {

        // Verify if there is more elements to be signed, if not, end batch with success.
        if (step.elemId >= batchElemIds.length) {
            onBatchCompleted(step.fileId);
            return;
        }

        // Update signature number
        $('#signNum').text(step.elemId + 1);

        // Call POST batch-xml-signature/start route
        $.ajax({
            url: 'xml_element_batch_signature/start',
            method: 'POST',
            data: {
                elemId: batchElemIds[step.elemId],
                fileId: step.fileId ? step.fileId : null
            },
            dataType: 'json',
            success: function (token) {

                // Receive the token from REST PKI call, which identifies the signature process.
                step.token = token;

                // Execute the second step of the signature process, that is the signature computation.
                performSignature(step);
            },
            error: function (jqXHR, textStatus, errorThrown) {

                // Render error and stop signature
                addAlert('danger', 'An error has occurred while signing element\'s id \"' + batchElemIds[step.elemId] + '\": ' + (errorThrown || textStatus));
                $.unblockUI();
            }
        });
    }

    // -------------------------------------------------------------------------------------------------
    // Function that performs the second step of the signature process, which is the call to
    // Web PKI's signWithRestPki function using the token acquired on the first step.
    // -------------------------------------------------------------------------------------------------
    function performSignature(step) {
        // Call signWithRestPki() on the Web PKI component passing the token received from REST PKI and
        // the certificate selected by the user.
        pki.signWithRestPki({
            token: step.token,
            thumbprint: selectedCertThumbprint
        }).success(function () {

            // Complete signature with REST PKI
            completeSignature(step);

        }).error(function (error) {

            // Render error and stop signature
            addAlert('danger', 'An error has occurred while signing element\'s id \"' + batchElemIds[step.elemId] + '\": ' + error);
            $.unblockUI();
        });
    }

    // -------------------------------------------------------------------------------------------------
    // Function that performs the third step of the signature process, which is the call the
    // batch-xml-signature/complete POST route in order to complete the signature.
    // -------------------------------------------------------------------------------------------------
    function completeSignature(step) {

        // Call POST batch-xml-signature/complete route
        $.ajax({
            url: 'xml_element_batch_signature/complete',
            method: 'POST',
            data: {
                token: step.token,
                fileId: step.fileId
            },
            dataType: 'json',
            success: function (fileId) {

                // Receive the signed xml's name, it will be used on the next signature
                step.fileId = fileId;

                // Start the new signature
                step.elemId++;
                startSignature({ elemId: step.elemId, fileId: fileId});

            },
            error: function (jqXHR, textStatus, errorThrown) {

                // Render error and stop signature
                addAlert('danger', 'An error has occurred while signing element\'s id \"' + batchElemIds[step.elemId] + '\": ' + (errorThrown || textStatus));
                $.unblockUI();
            }
        });
    }

    // -------------------------------------------------------------------------------------------------
    // Function called once the batch is completed.
    // -------------------------------------------------------------------------------------------------
    function onBatchCompleted(filename) {

        // Unblock the UI
        $.unblockUI();

        // Prevent user from clicking "sign batch" again (our logic isn't prepared for that)
        $('#signButton').prop('disabled', true);

        // Show signature results and hide signature form
        $('#signedFileLink').attr('href', 'uploads/' + filename);
        $('#signatureResults').show();
        $('#signForm').hide();
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

    // -------------------------------------------------------------------------------------------------
    // Function called to notify the user with some message
    // -------------------------------------------------------------------------------------------------
    function addAlert(type, message) {
        errorPanel.append(
            '<div class="alert alert-' + type + ' alert-dismissible">' +
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
            '<span>' + message + '</span>' +
            '</div>');
    }

    return {
        init: init
    };

})();
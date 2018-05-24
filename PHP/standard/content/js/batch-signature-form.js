// -----------------------------------------------------------------------------------------------------
// This file contains logic for calling the Web PKI component to sign a batch of documents. It is only 
// an example, feel free to alter it to meet your application's needs.
// -----------------------------------------------------------------------------------------------------
var batchSignatureForm = (function() {

    // The Javascript class "Queue" defined here helps to process the documents in the batch. You don't
    // necessarily need to understand this code, only how to use it (see the usage below on the function
    // startBatch).
    (function () {
        window.Queue = function () {
            this.items = [];
            this.writerCount = 0;
            this.readerCount = 0;
        };
        window.Queue.prototype.add = function (e) {
            this.items.push(e);
        };
        window.Queue.prototype.addRange = function (array) {
            for (var i = 0; i < array.length; i++) {
                this.add(array[i]);
            }
        };
        var _process = function (inQueue, processor, outQueue, endCallback) {
            var obj = inQueue.items.shift();
            if (obj !== undefined) {
                processor(obj, function (result) {
                    if (result != null && outQueue != null) {
                        outQueue.add(result);
                    }
                    _process(inQueue, processor, outQueue, endCallback);
                });
            } else if (inQueue.writerCount > 0) {
                setTimeout(function () {
                    _process(inQueue, processor, outQueue, endCallback);
                }, 200);
            } else {
                --inQueue.readerCount;
                if (outQueue != null) {
                    --outQueue.writerCount;
                }
                if (inQueue.readerCount == 0 && endCallback) {
                    endCallback();
                }
            }
        };
        window.Queue.prototype.process = function (processor, options) {
            var threads = options.threads || 1;
            this.readerCount = threads;
            if (options.output) {
                options.output.writerCount = threads;
            }
            for (var i = 0; i < threads; i++) {
                _process(this, processor, options.output, options.completed);
            }
        };
    })();

    // Auxiliary global variables.
    var selectedCertThumbprint = null;
    var startQueue = null;
    var performQueue = null;
    var completeQueue = null;
    var batchDocIds = null;

    // Create an instance of the LacunaWebPKI object.
    var pki = new LacunaWebPKI(_webPkiLicense);

    // -------------------------------------------------------------------------------------------------
    // Function called once the page is loaded.
    // -------------------------------------------------------------------------------------------------
    function init(args) {

        // Receive the documents IDs.
        batchDocIds = args.documentsIds;

        // Wireup of button clicks.
        args.signButton.click(sign);
        args.refreshButton.click(refresh);

        // Block the UI while we get things ready.
        $.blockUI();

        // Render documents to be signed.
        var docList = $('#docList');
        for (var i = 0; i < batchDocIds.length; i++) {
            var docId = batchDocIds[i];
            docList.append(
                $('<li />').append(
                    $('<a />').text('Document ' + docId).attr('href', "content/0" + (docId % 10) + ".pdf")
                )
            );
        }

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
        $.blockUI();
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

        // Block the UI while we perform the signature.
        $.blockUI();

        // Get the thumbprint of the selected certificate and store it in a global variable (we'll need
        // it later).
        selectedCertThumbprint = $('#certificateSelect').val();

        // Call Web PKI to preauthorize the signatures, so that the user only sees one confirmation
        // dialog.
        pki.preauthorizeSignatures({
            certificateThumbprint: selectedCertThumbprint,
            signatureCount: batchDocIds.length // Number of signatures to be authorized by the user.
        }).success(startBatch); // Callback to be called if the user authorizes the signatures.
    }

    // -------------------------------------------------------------------------------------------------
    // Function called when the user authorizes the signatures.
    // -------------------------------------------------------------------------------------------------
    function startBatch() {

        /*
         For each document, we must perform 3 actions in sequence:

            1. Start the signature    : call batch-signature-start.php to start the signature and get
                                        the signature process token;
            2. Perform the signature  : call Web PKI's method signWithRestPki with the token;
            3. Complete the signature : call batch-signature-complete.php to notify that the signature
                                        is complete.

         We'll use the Queue Javascript class defined above in order to perform these steps
         simultaneously.
         */

        // Create the queues.
        startQueue = new Queue();
        performQueue = new Queue();
        completeQueue = new Queue();

        // Add all documents to the first ("start") queue
        for (var i = 0; i < batchDocIds.length; i++) {
            startQueue.add({ index: i, docId: batchDocIds[i] });
        }

        /*
         Process each queue placing the result on the next queue, forming a sort of "assembly line":

         startQueue                              performQueue                               completeQueue
         -------------                            -------------                              -------------
         XXXXXXX        ->  (startSignature)  ->             XX  ->  (performSignature)  ->            XXX  ->  (completeSignature)
         -------------         2 threads          -------------          2 threads           -------------           2 threads
         */
        startQueue.process(startSignature, { threads: 2, output: performQueue });
        performQueue.process(performSignature, { threads: 2, output: completeQueue });
        completeQueue.process(completeSignature, { threads: 2, completed: onBatchCompleted });
        // onBatchCompleted is a callback for when the last queue is completely processed.

        // Notice: the thread count on each call above is already optimized, increasing the number of
        // threads will not enhance the performance significatively.
    }

    // -------------------------------------------------------------------------------------------------
    // Function that performs the first step described above for each document, which is the call
    // batch-signature-start.php in order to start the signature and get the token associated with the
    // signature process.
    //
    // This function is called by the Queue.process function, taking documents from the "start" queue.
    // Once we're done, we'll call the "done" callback passing the document, and the Queue.process
    // function will place the document on the "perform" queue to await processing.
    // -------------------------------------------------------------------------------------------------
    function startSignature(step, done) {
        // Call the server asynchronously to start the signature (the server will call REST PKI and
        // will return the signature operation token).
        $.ajax({
            url: 'batch-signature-start.php',
            method: 'POST',
            data: {
                id: step.docId
            },
            dataType: 'json',
            success: function (token) {
                // Add the token to the document information (we'll need it in the second step);
                step.token = token;
                // Call the "done" callback signalling we're done with the document;
                done(step);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                // Render error;
                renderFail(step, errorThrown || textStatus);
                // Call the "done" callback with no argument, signalling the document should not go to
                // the next queue.
                done();
            }
        });
    }

    // -------------------------------------------------------------------------------------------------
    // Function that performs the second step described above for each document, which is the call to
    // Web PKI's signWithRestPki function using the token acquired on the first step.
    //
    // This function is called by the Queue.process function, taking documents from the "perform" queue.
    // Once we're done, we'll call the "done" callback passing the document, and the Queue.process
    // function will place the document on the "complete" queue to await processing.
    // -------------------------------------------------------------------------------------------------
    function performSignature(step, done) {
        // Call signWithRestPki() on the Web PKI component passing the token received from REST PKI and
        // the certificate selected by the user.
        pki.signWithRestPki({
            token: step.token,
            thumbprint: selectedCertThumbprint
        }).success(function () {
            // Call the "done" callback signalling we're done with the document.
            done(step);
        }).error(function (error) {
            // Render error.
            renderFail(step, error);
            // Call the "done" callback with no argument, signalling the document should not go to the
            // next queue.
            done();
        });
    }

    // -------------------------------------------------------------------------------------------------
    // Function that performs the third step described above for each document, which is the call
    // batch-signature-complete.php in order to complete the signature.
    //
    // This function is called by the Queue.process function, taking documents from the "complete" queue.
    // Once we're done, we'll call the "done" callback passing the document. Once all documents are
    // processed, the Queue.process will call the "onBatchCompleted" function.
    // -------------------------------------------------------------------------------------------------
    function completeSignature(step, done) {
        // Call the server asynchronously to notify that the signature has been performed.
        $.ajax({
            url: 'batch-signature-complete.php',
            method: 'POST',
            data: {
                token: step.token // The signature process token is guaranteed to be URL-safe.
            },
            dataType: 'json',
            success: function (filename) {
                step.filename = filename;
                // Render success.
                renderSuccess(step);
                // Call the "done" callback signalling we're done with the document.
                done(step);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                // Render error
                renderFail(step, errorThrown || textStatus);
                // Call the "done" callback with no argument, signalling the document should not go to
                // the next queue.
                done();
            }
        });
    }

    // -------------------------------------------------------------------------------------------------
    // Function called once the batch is completed.
    // -------------------------------------------------------------------------------------------------
    function onBatchCompleted() {
        // Notify the user and unblock the UI.
        addAlert('info', 'Batch processing completed');
        // Prevent user from clicking "sign batch" again (our logic isn't prepared for that).
        $('#signButton').prop('disabled', true);
        // Unblock the UI.
        $.unblockUI();
    }

    // -------------------------------------------------------------------------------------------------
    // Function that renders a document as completed successfully.
    // -------------------------------------------------------------------------------------------------
    function renderSuccess(step) {
        var docLi = $('#docList li').eq(step.index);
        docLi.append(
            document.createTextNode(' ')
        ).append(
            $('<span />').addClass('glyphicon glyphicon-arrow-right')
        ).append(
            document.createTextNode(' ')
        ).append(
            $('<a />').text(step.filename).attr('href', 'app-data/' + step.filename)
        );
    }

    // -------------------------------------------------------------------------------------------------
    // Function that renders a document as failed.
    // -------------------------------------------------------------------------------------------------
    function renderFail(step, error) {
        addAlert('danger', 'An error has occurred while signing Document ' + step.docId + ': ' + error);
        var docLi = $('#docList li').eq(step.index);
        docLi.append(
            document.createTextNode(' ')
        ).append(
            $('<span />').addClass('glyphicon glyphicon-remove')
        );
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
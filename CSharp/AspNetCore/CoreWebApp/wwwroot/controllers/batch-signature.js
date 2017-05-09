'use strict';
app.controller('batchSignatureController', ['$scope', '$http', 'blockUI', 'util', function ($scope, $http, blockUI, util) {

    // The Javascript class "Queue" defined here helps to process the documents in the batch.You don't necessarily need to
    // understand this code, only how to use it (see the usage below on the function startBatch)
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

    $scope.certificates = [];
    $scope.selectedCertificate = null;
    $scope.batch = getBatch(1, 30);
    $scope.completed = false;

    // Auxiliary global variables
    var selectedCertThumbprint = null;
    var startQueue = null;
    var performQueue = null;
    var completeQueue = null;

    // Create an instance of the LacunaWebPKI object
    var pki = new LacunaWebPKI();

    // -------------------------------------------------------------------------------------------------
	// Function that initializes the Web PKI component
	// -------------------------------------------------------------------------------------------------
    var init = function () {

        // Block the UI while we get things ready
        blockUI.start();

        // Call the init() method on the LacunaWebPKI object, passing a callback for when
        // the component is ready to be used and another to be called when an error occurrs
        // on any of the subsequent operations. For more information, see:
        // https://webpki.lacunasoftware.com/#/Documentation#coding-the-first-lines
        // http://webpki.lacunasoftware.com/Help/classes/LacunaWebPKI.html#method_init
        pki.init({
            ready: loadCertificates, // as soon as the component is ready we'll load the certificates
            defaultError: onWebPkiError, // generic error callback
            angularScope: $scope // Pass Angularjs scope for WebPKI
        });
    }

    // -------------------------------------------------------------------------------------------------
    // Function called when the user clicks the "Refresh" button
    // -------------------------------------------------------------------------------------------------
    $scope.refresh = function () {
        // Block the UI while we load the certificates
        blockUI.start();
        // Invoke the loading of the certificates
        loadCertificates();
    };

    // -------------------------------------------------------------------------------------------------
    // Function that loads the certificates, either on startup or when the user
    // clicks the "Refresh" button. At this point, the UI is already blocked.
    // -------------------------------------------------------------------------------------------------
    var loadCertificates = function () {

        // Call the listCertificates() method to list the user's certificates. For more information see
        // http://webpki.lacunasoftware.com/Help/classes/LacunaWebPKI.html#method_listCertificates
        pki.listCertificates({

            // specify that expired certificates should be ignored
            //filter: pki.filters.isWithinValidity,

            // in order to list only certificates within validity period and having a CPF (ICP-Brasil), use this instead:
            //filter: pki.filters.all(pki.filters.hasPkiBrazilCpf, pki.filters.isWithinValidity),

        }).success(function (certificates) {

            // Remember the selected certificate (see below)
            var originalSelected = ($scope.selectedCertificate || {}).thumbprint;

            // Set available certificates on scope
            $scope.certificates = certificates;

            // Recover previous selection
            angular.forEach(certificates, function (c) {
                if (c.thumbprint === originalSelected) {
                    $scope.selectedCertificate = c;
                }
            });

            // once the certificates have been listed, unblock the UI
            blockUI.stop();

        });
    };

    $scope.getCertificateDisplayName = function (cert) {
        return cert.subjectName + ' (expires on ' + cert.validityEnd.toDateString() + ', issued by ' + cert.issuerName + ')';
    };

    // -------------------------------------------------------------------------------------------------
    // Function called when the user clicks the "Sign Batch" button
    // -------------------------------------------------------------------------------------------------
    $scope.signBatch = function () {
        if ($scope.selectedCertificate == null) {
            util.showMessage('Message', 'Please select a certificate');
            return;
        }

        // Block the UI
        blockUI.start();

        // Call Web PKI to preauthorize the signatures, so that the user only sees one confirmation dialog
        pki.preauthorizeSignatures({
            certificateThumbprint: $scope.selectedCertificate.thumbprint,
            signatureCount: $scope.batch.length // number of signatures to be authorized by the user
        }).success(startBatch); // callback to be called if the user authorizes the signatures
    };

    // -------------------------------------------------------------------------------------------------
    // Function called when the user authorizes the signatures
    // -------------------------------------------------------------------------------------------------
    var startBatch = function () {

        /*
            For each document, we must perform 3 actions in sequence:

            1. Start the signature    : call the action Api/BatchSignature/Start to start the signature and get the signature process token
            2. Perform the signature  : call Web PKI's method signWithRestPki with the token
            3. Complete the signature : call the action Api/BatchSignature/Complete to notify that the signature is complete

            We'll use the Queue Javascript class defined above in order to perform these steps simultaneously.
         */

        // Create the queues
        startQueue = new Queue();
        performQueue = new Queue();
        completeQueue = new Queue();

        // Add all documents to the first ("start") queue
        for (var i = 0; i < $scope.batch.length; i++) {
            startQueue.add({ index: i, docId: $scope.batch[i].docId });
        }

        /*
            Process each queue placing the result on the next queue, forming a sort of "assembly line":

                 startQueue                              performQueue                               completeQueue
                -------------                            -------------                              -------------
                      XXXXXXX  ->  (startSignature)  ->             XX  ->  (performSignature)  ->            XXX  ->  (completeSignature)
                -------------         2 threads          -------------          2 threads           -------------           2 threads
         */
        startQueue.process(startSignature, { threads: 2, output: performQueue });
        performQueue.process(performSignature, { threads: 2, output: completeQueue });
        completeQueue.process(completeSignature, { threads: 2, completed: onBatchCompleted }); // onBatchCompleted is a callback for when the last queue is completely processed

        // Notice: the thread count on each call above is already optimized, increasing the number of threads will
        // not enhance the performance significatively
    };

    // -------------------------------------------------------------------------------------------------
	// Function that performs the first step described above for each document, which is the call to the
	// action Api/BatchSignature/Start in order to start the signature and get the token associated with the
	// signature process.
	//
	// This function is called by the Queue.process function, taking documents from the "start" queue.
	// Once we're done, we'll call the "done" callback passing the document, and the Queue.process
	// function will place the document on the "perform" queue to await processing.
	// -------------------------------------------------------------------------------------------------
    var startSignature = function(step, done) {
        // Call the server asynchronously to start the signature (the server will call REST PKI and will return the signature operation token)
        $http.post('Api/BatchSignature/Start/' + step.docId).then(function (response) {
            // Add the token to the document information (we'll need it in the second step)
            step.token = response.data;
            // Call the "done" callback signalling we're done with the document
            done(step);
        }, function (error) {
            // Render error
            renderFail(step, error.data.message);
            // Call the "done" callback with no argument, signalling the document should not go to the next queue
            done();
        });
    };

    // -------------------------------------------------------------------------------------------------
	// Function that performs the second step described above for each document, which is the call to
	// Web PKI's signWithRestPki function using the token acquired on the first step.
	//
	// This function is called by the Queue.process function, taking documents from the "perform" queue.
	// Once we're done, we'll call the "done" callback passing the document, and the Queue.process
	// function will place the document on the "complete" queue to await processing.
	// -------------------------------------------------------------------------------------------------
    var performSignature = function(step, done) {
        // Call signWithRestPki() on the Web PKI component passing the token received from REST PKI and the certificate selected by the user.
        pki.signWithRestPki({
            thumbprint: $scope.selectedCertificate.thumbprint,
            token: step.token
        }).success(function () {
            // Call the "done" callback signalling we're done with the document
            done(step);
        }).error(function (error) {
            // Render error
            renderFail(step, error);
            // Call the "done" callback with no argument, signalling the document should not go to the next queue
            done();
        });
    };

    // -------------------------------------------------------------------------------------------------
	// Function that performs the third step described above for each document, which is the call to the
	// action Api/BatchSignature/Complete in order to complete the signature.
	//
	// This function is called by the Queue.process function, taking documents from the "complete" queue.
	// Once we're done, we'll call the "done" callback passing the document. Once all documents are
	// processed, the Queue.process will call the "onBatchCompleted" function.
	// -------------------------------------------------------------------------------------------------
    var completeSignature = function (step, done) {
        // Call the server asynchronously to notify that the signature has been performed
        $http.post('Api/BatchSignature/Complete/' + step.token).then(function (response) {
            step.signedFileId = response.data;
            // Render success
            renderSuccess(step);
            // Call the "done" callback signalling we're done with the document
            done(step);
        }, function (error) {
            // Render error
            renderFail(step, error.data.message);
            // Call the "done" callback with no argument, signalling the document should not go to the next queue
            done();
        });
    };

    // -------------------------------------------------------------------------------------------------
	// Function called once the batch is completed.
	// -------------------------------------------------------------------------------------------------
    var onBatchCompleted = function () {
        $scope.$applyAsync(function () {
            // Notify the user and unblock the UI
            util.addAlert('info', 'Batch processing completed');
            // Prevent user from clicking "sign batch" again (our logic isn't prepared for that)
            $scope.completed = true;
            // Unblock the UI
            blockUI.stop();
        });
    };

    // -------------------------------------------------------------------------------------------------
	// Function that renders a documument as completed successfully
	// -------------------------------------------------------------------------------------------------
    var renderSuccess = function (step) {
        $scope.batch[step.index].done = true;
        $scope.batch[step.index].success = true;
        $scope.batch[step.index].signedFileId = step.signedFileId;
    };

    // -------------------------------------------------------------------------------------------------
	// Function that renders a documument as failed
	// -------------------------------------------------------------------------------------------------
    var renderFail = function (step, error) {
        util.addAlert('danger', 'An error has occurred while signing Document ' + step.docId + ': ' + error);
        $scope.batch[step.index].done = true;
        $scope.batch[step.index].success = false;
    };

    // -------------------------------------------------------------------------------------------------
    // Function called if an error occurs on the Web PKI component
    // -------------------------------------------------------------------------------------------------
    var onWebPkiError = function (message, error, origin) {
        // Unblock the UI
        blockUI.stop();
        // Log the error to the browser console (for debugging purposes)
        if (console) {
            console.log('An error has occurred on the signature browser component: ' + message, error);
        }
        // Show the message to the user
        util.showMessage('Error', message);
    };

    function getBatch(start, end) {
        var array = [];
        for (var i = start; i <= end; i++) {
            array.push({
                docId: i,
                signedFileId: null,
                done: false,
                success: null
            });
        }
        return array;
    }

    init();

}]);
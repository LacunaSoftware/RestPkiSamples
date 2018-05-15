<?php

/**
 * This action renders the batch signature page.
 *
 * Notice that the only thing we'll do on the server-side at this point is determine the IDs of the documents
 * to be signed. The page will handle each document one by one and will call the server asynchronously to
 * start and complete each signature.
 */

require __DIR__ . '/vendor/autoload.php';

// It is up to your application's business logic to determine which element id will compose the batch and which file
// will be signed.
$elementsIds = array_map(function ($id) {
    return "ID2102100000000000000000000000000000000000008916" . sprintf("%02d", $id);
}, range(1, 10));

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Batch Samples</title>
    <?php include 'includes.php' // jQuery and other libs (used only to provide a better user experience, but NOT required to use the Web PKI component) ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely. ?>

<div class="container">

    <?php // Messages about the signature process will be rendered in here. ?>
    <div id="messagesPanel"></div>

    <h2>Batch Signature of XML Elements</h2>

    <div id="signatureResult" class="form-group" style="display: none;">
        <h3>File signed successfully!</h3>

        <label for="actions">Actions:</label>
        <ul id="actions">
            <li><a id="signedFileLink">Download the signed file</a></li>
            <li><a id="openSignatureLink">Open/validate the signed file</a></li>
        </ul>
    </div>

    <form id="signForm" method="POST">


        <div class="form-group">
            <label>File to sign</label>

            <p>You are signing a batch of nodes of <a href='content/EventoManifesto.xml'>this sample XML</a>.</p>
        </div>

        <?php
        // Render a select (combo box) to list the user's certificates. For now it will be
        // empty, we'll populate it later on (see batch-xml-element-signature-form.js).
        ?>
        <div class="form-group">
            <label for="certificateSelect">Choose a certificate</label>
            <select id="certificateSelect" class="form-control"></select>
        </div>

        <?php
        // Action buttons. Notice that the "Sign File" button is NOT a submit button. When the user clicks the button,
        // we must first use the Web PKI component to perform the client-side computation necessary and only when
        // that computation is finished we'll submit the form programmatically (see batch-xml-element-signature-form.js).
        ?>
        <button id="signButton" type="button" class="btn btn-primary">Sign Batch</button>
        <button id="refreshButton" type="button" class="btn btn-default">Refresh Certificates</button>

    </form>

</div>


<?php
// The file below contains the JS lib for accessing the Web PKI component. For more information, see:
// https://webpki.lacunasoftware.com/#/Documentation
?>
<script src="content/js/lacuna-web-pki-2.6.1.js"></script>

<?php
// The file below contains the logic for calling the Web PKI component. It is only an example, feel free to alter it
// to meet your application's needs. You can also bring the code into the javascript block below if you prefer.
?>
<script src="content/js/batch-xml-element-signature-form.js"></script>
<script>
    $(document).ready(function () {
        // Once the page is ready, we call the init() function on the javascript code (see batch-xml-element-signature-form.js).
        batchXmlElementSignatureForm.init({
            elementsIds: <?= json_encode($elementsIds); ?>, // The IDs of the elements of the document.
            certificateSelect: $('#certificateSelect'),     // The <select> element (combo box) to list the certificates.
            refreshButton: $('#refreshButton'),             // The "refresh" button.
            signButton: $('#signButton')                    // The button that initiates the operation.
        });
    });
</script>

</body>
</html>


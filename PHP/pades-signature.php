<!--/*
	This is the page for PAdES signatures. Actual logic can be found at:
	- Client-side: src/main/resources/static/js/app/pades-signature.js
	- Server-side: src/main/java/sample/models/PadesSignatureController.cs
*/-->
<!DOCTYPE html>
<html>
<head>
    <?php include 'head.php' ?>
</head>
<body>

<?php include 'top.php' ?>

<div class="container">

    <h2>PAdES Signature</h2>

    <form>
        <div class="form-group">
            <label>File to sign</label>
            <p>You'll be signing <a href='content/SampleDocument.pdf'>this sample document</a>.</p>
        </div>
        <div class="form-group">
            <label for="certificateSelect">Choose a certificate</label>
            <select id="certificateSelect" class="form-control"></select>
        </div>
        <button id="signButton" type="button" class="btn btn-primary">Sign File</button>
        <button id="refreshButton" type="button" class="btn btn-default">Refresh Certificates</button>
    </form>

    <fieldset id="validationResultsPanel" style="display: none;">
        <legend>Validation Results</legend>
        <textarea readonly="true" rows="25" style="width: 100%"></textarea>
    </fieldset>

    <script src="content/js/lacuna-web-pki-2.2.1.js"></script>
    <script src="content/js/app/pades-signature.js"></script>

</div>
</body>
</html>

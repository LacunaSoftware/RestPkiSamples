<?php

/*
 * This file receives the form submission from cades-signature.php. We'll call REST PKI to complete the signature.
 */

require __DIR__ . '/vendor/autoload.php';

use Lacuna\RestPki\CadesSignatureFinisher2;

// Get the token for this signature (rendered in a hidden input field, see cades-signature.php).
$token = $_POST['token'];

// Instantiate the CadesSignatureFinisher2 class, responsible for completing the signature process.
$signatureFinisher = new CadesSignatureFinisher2(getRestPkiClient());

// Set the token.
$signatureFinisher->token = $token;

// Call the finish() method, which finalizes the signature process and returns a SignatureResult object.
$signatureResult = $signatureFinisher->finish();

// The "certificate" property of the SignatureResult object contains information about the certificate used by the user
// to sign the file.
$signerCert = $signatureResult->certificate;

// At this point, you'd typically store the CMS on your database. For demonstration purposes, we'll
// store the CMS on a temporary folder publicly accessible and render a link to it.

createAppData(); // Make sure the "app-data" folder exists (util.php).
$filename = uniqid() . ".p7s";

// The SignatureResult object has functions for writing the signature file to a local file (writeToFile()) and to get
// its raw contents (getContent()). For large files, use writeToFile() in order to avoid memory allocation issues.
$signatureResult->writeToFile("app-data/{$filename}");

?><!DOCTYPE html>
<html>
<head>
    <title>CAdES Signature</title>
    <?php include 'includes.php' // jQuery and other libs (used only to provide a better user experience, but NOT required to use the Web PKI component). ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely. ?>

<div class="container">

    <h2>CAdES Signature</h2>

    <p>File signed successfully!</p>

    <p>
        Signer information:
    <ul>
        <li>Subject: <?= $signerCert->subjectName->commonName ?></li>
        <li>Email: <?= $signerCert->emailAddress ?></li>
        <li>
            ICP-Brasil fields
            <ul>
                <li>Tipo de certificado: <?= $signerCert->pkiBrazil->certificateType ?></li>
                <li>CPF: <?= $signerCert->pkiBrazil->cpf ?></li>
                <li>Responsavel: <?= $signerCert->pkiBrazil->responsavel ?></li>
                <li>Empresa: <?= $signerCert->pkiBrazil->companyName ?></li>
                <li>CNPJ: <?= $signerCert->pkiBrazil->cnpj ?></li>
                <li>
                    RG: <?= $signerCert->pkiBrazil->rgNumero . " " . $signerCert->pkiBrazil->rgEmissor . " " . $signerCert->pkiBrazil->rgEmissorUF ?></li>
                <li>OAB: <?= $signerCert->pkiBrazil->oabNumero . " " . $signerCert->pkiBrazil->oabUF ?></li>
            </ul>
        </li>
    </ul>
    </p>

    <h3>Actions:</h3>
    <ul>
        <li><a href="app-data/<?= $filename ?>">Download the signed file</a></li>
        <li><a href="open-cades-signature.php?userfile=<?= $filename ?>">Open/validate the signed file</a></li>
        <li><a href="cades-signature.php?cmsfile=<?= $filename ?>">Co-sign with another certificate</a></li>
    </ul>

</div>

</body>
</html>

<?php

/*
 * This file receives the form submission from xml-full-signature.php. We'll call REST PKI to complete the signature.
 */

// The file RestPki.php contains the helper classes to call the REST PKI API
require_once 'RestPki.php';

// The file util.php contains the function getRestPkiClient(), which gives us an instance of the RestPkiClient class
// initialized with the API access token
require_once 'util.php';

use Lacuna\XmlSignatureFinisher;

// Get the token for this signature (rendered in a hidden input field, see xml-full-signature.php)
$token = $_POST['token'];

// Instantiate the XmlSignatureFinisher class, responsible for completing the signature process
$signatureFinisher = new XmlSignatureFinisher(getRestPkiClient());

// Set the token
$signatureFinisher->setToken($token);

// Call the finish() method, which finalizes the signature process and returns the signed XML
$signedXml = $signatureFinisher->finish();

// Get information about the certificate used by the user to sign the file. This method must only be called after
// calling the finish() method.
$signerCert = $signatureFinisher->getCertificateInfo();

// At this point, you'd typically store the signed XML on your database. For demonstration purposes, we'll
// store the PDF on a temporary folder publicly accessible and render a link to it.

$filename = uniqid() . ".xml";
createAppData(); // make sure the "app-data" folder exists (util.php)
file_put_contents("app-data/{$filename}", $signedXml);

?><!DOCTYPE html>
<html>
<head>
    <title>Full XML signature (enveloped signature)</title>
    <?php include 'includes.php' // jQuery and other libs (used only to provide a better user experience, but NOT required to use the Web PKI component) ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely ?>

<div class="container">

    <h2>Full XML signature (enveloped signature)</h2>

    <p>File signed successfully! <a href="app-data/<?= $filename ?>">Click here to download the signed file</a></p>

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

</div>

</body>
</html>

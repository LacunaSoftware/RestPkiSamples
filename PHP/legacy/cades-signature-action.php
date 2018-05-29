<?php

/*
 * This file receives the form submission from cades-signature.php. We'll call REST PKI to complete the signature.
 */

require __DIR__ . '/vendor/autoload.php';

use Lacuna\RestPki\Legacy\CadesSignatureFinisher;

// Get the token for this signature (rendered in a hidden input field, see cades-signature.php).
$token = $_POST['token'];

// Get an instance of the CadesSignatureFinisher class, responsible for completing the signature process.
$signatureFinisher = new CadesSignatureFinisher(getRestPkiClient());

// Set the token.
$signatureFinisher->setToken($token);

// Call the finish() method, which finalizes the signature process and returns the CMS (p7s file) bytes.
$cms = $signatureFinisher->finish();

// Get information about the certificate used by the user to sign the file. This method must only be called after
// calling the finish() method.
$signerCert = $signatureFinisher->getCertificateInfo();

// At this point, you'd typically store the CMS on your database. For demonstration purposes, we'll store the CMS on a
// temporary folder publicly accessible and render a link to it.

createAppData(); // make sure the "app-data" folder exists (util.php)
$filename = uniqid() . ".p7s";
file_put_contents("app-data/{$filename}", $cms);

?>

<!DOCTYPE html>
<html>
<head>
    <title>PAdES Signature</title>
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
        <li>Subject: <?php echo $signerCert->subjectName->commonName; ?></li>
        <li>Email: <?php echo $signerCert->emailAddress; ?></li>
        <li>
            ICP-Brasil fields
            <ul>
                <li>Tipo de certificado: <?php echo $signerCert->pkiBrazil->certificateType; ?></li>
                <li>CPF: <?php echo $signerCert->pkiBrazil->cpf; ?></li>
                <li>Responsavel: <?php echo $signerCert->pkiBrazil->responsavel; ?></li>
                <li>Empresa: <?php echo $signerCert->pkiBrazil->companyName; ?></li>
                <li>CNPJ: <?php echo $signerCert->pkiBrazil->cnpj; ?></li>
                <li>
                    RG: <?php echo $signerCert->pkiBrazil->rgNumero . " " . $signerCert->pkiBrazil->rgEmissor . " " . $signerCert->pkiBrazil->rgEmissorUF ?></li>
                <li>OAB: <?php echo $signerCert->pkiBrazil->oabNumero . " " . $signerCert->pkiBrazil->oabUF ?></li>
            </ul>
        </li>
    </ul>
    </p>

    <h3>Actions:</h3>
    <ul>
        <li><a href="app-data/<?php echo $filename ?>">Download the signed file</a></li>
        <li><a href="open-cades-signature.php?userfile=<?php echo $filename ?>">Open/validate the signed file</a></li>
        <li><a href="cades-signature.php?cmsfile=<?php echo $filename ?>">Co-sign with another certificate</a></li>
    </ul>

</div>

</body>
</html>

<?php

/*
 * This file performs a PAdES signature using REST PKI and a PKCS #12 file located on the server-side.
 */

require __DIR__ . '/vendor/autoload.php';

use Lacuna\RestPki\PadesSignatureStarter;
use Lacuna\RestPki\PadesVisualPositioningPresets;
use Lacuna\RestPki\StandardSignaturePolicies;
use Lacuna\RestPki\PadesMeasurementUnits;
use Lacuna\RestPki\StandardSecurityContexts;
use Lacuna\RestPki\PadesSignatureFinisher2;

// Read the PKCS #12 file,
if (!$certStore = file_get_contents("content/Pierre de Fermat.pfx")) {
    throw new \Exception("Unable to read PKCS #12 file");
}
if (!openssl_pkcs12_read($certStore, $certObj, "1234")) {
    throw new \Exception("Unable to open the PKCS #12 file");
}

// 1. Start the signature with REST PKI

// Instantiate the PadesSignatureStarter class, responsible for receiving the signature elements and start the signature
// process.
$signatureStarter = new PadesSignatureStarter(getRestPkiClient());

// If the user was redirected here by upload.php (signature with file uploaded by user), the "userfile" URL argument
// will contain the filename under the "app-data" folder. Otherwise (signature with server file), we'll sign a sample
// document.
$userfile = isset($_GET['userfile']) ? $_GET['userfile'] : null;
if (!empty($userfile)) {
    $signatureStarter->setPdfToSignFromPath("app-data/{$userfile}");
} else {
    $signatureStarter->setPdfToSignFromPath('content/SampleDocument.pdf');
}

// Set the signer certificate.
$signatureStarter->setSignerCertificateRaw($certObj['cert']);

// Set the unit of measurement used to edit the pdf marks and visual representations
$signatureStarter->measurementUnits = PadesMeasurementUnits::CENTIMETERS;

// Set the signature policy.
$signatureStarter->signaturePolicy = StandardSignaturePolicies::PADES_BASIC;

// For this sample, we'll use the Lacuna Test PKI in order to accept our test certificate used
// above ("Pierre de Fermat"). This security context should be used ***** FOR DEVELOPMENT PUPOSES ONLY ****.
$signatureStarter->securityContext = StandardSecurityContexts::LACUNA_TEST;

// Set the visual representation to the signature. We have encapsulated this code (on util-pades.php) to be used on
// various PAdES examples.
$signatureStarter->visualRepresentation = getVisualRepresentation(getRestPkiClient());

/*
	Optionally, add marks to the PDF before signing. These differ from the signature visual representation in that
	they are actually changes done to the document prior to signing, not binded to any signature. Therefore, any number
	of marks can be added, for instance one per page, whereas there can only be one visual representation per signature.
	However, since the marks are in reality changes to the PDF, they can only be added to documents which have no
    previous signatures, otherwise such signatures would be made invalid by the changes to the document (see property
	PadesSignatureStarter::bypassMarksIfSigned). This problem does not occurr with signature visual representations.

	We have encapsulated this code in a method to include several possibilities depending on the argument passed.
	Experiment changing the argument to see different examples of PDF marks. Once you decide which is best for your
    case, you can place the code directly here.
*/
//array_push($signatureStarter->pdfMarks, getPdfMark(1));

// Call the start() method, which initiates the signature. This yields the parameters for the signature using the
// certificate.
$signatureParams = $signatureStarter->start();


// 2. Perform the signature

// Perform the signature using the parameters returned by Rest PKI with the key extracted from PKCS #12.
openssl_sign($signatureParams->toSignData, $signature, $certObj['pkey'], $signatureParams->openSslSignatureAlgorithm);


// 3. Complete the signature with REST PKI

// Instantiate the PadesSignatureFinisher2 class, responsible for completing the signature process.
$signatureFinisher = new PadesSignatureFinisher2(getRestPkiClient());

// Set the token.
$signatureFinisher->token = $signatureParams->token;

// Set the signature.
$signatureFinisher->setSignatureRaw($signature);

// Call the finish() method, which finalizes the signature process and returns a SignatureResult object.
$signatureResult = $signatureFinisher->finish();

// The "certificate" property of the SignatureResult object contains information about the certificate used by the user
// to sign the file.
$signerCert = $signatureResult->certificate;

// At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
// store the PDF on a temporary folder publicly accessible and render a link to it.

$filename = uniqid() . ".pdf";
createAppData(); // make sure the "app-data" folder exists (util.php).

// The SignatureResult object has functions for writing the signature file to a local file (writeToFile()) and to get
// its raw contents (getContent()). For large files, use writeToFile() in order to avoid memory allocation issues.
$signatureResult->writeToFile("app-data/{$filename}");

?><!DOCTYPE html>
<html>
<head>
    <title>PAdES Signature</title>
    <?php include 'includes.php' // jQuery and other libs (used only to provide a better user experience, but NOT required to use the Web PKI component). ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely. ?>

<div class="container">

    <h2>PAdES Signature with server key</h2>

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
        <li><a href="printer-friendly-version.php?file=<?= $filename ?>">Download a printer-friendly version of the signed file</a></li>
        <li><a href="open-pades-signature.php?userfile=<?= $filename ?>">Open/validate the signed file</a></li>
    </ul>

</div>

</body>
</html>

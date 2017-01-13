<?php

/*
 * This file performs a PAdES signature using REST PKI and a PKCS #12 file located on the server-side.
 */

require __DIR__ . '/vendor/autoload.php';

use Lacuna\RestPki\PadesSignatureStarter;
use Lacuna\RestPki\StandardSignaturePolicies;
use Lacuna\RestPki\PadesMeasurementUnits;
use Lacuna\RestPki\StandardSecurityContexts;
use Lacuna\RestPki\PadesSignatureFinisher2;

// Read the PKCS #12 file
if (!$certStore = file_get_contents("content/Pierre de Fermat.pfx")) {
    throw new \Exception("Unable to read PKCS #12 file");
}
if (!openssl_pkcs12_read($certStore, $certObj, "1234")) {
    throw new \Exception("Unable to open the PKCS #12 file");
}

// Instantiate the PadesSignatureStarter class, responsible for receiving the signature elements and start the signature
// process
$signatureStarter = new PadesSignatureStarter(getRestPkiClient());

// Set the signer certificate
$signatureStarter->setSignerCertificate($certObj['cert']);

// Set the unit of measurement used to edit the pdf marks and visual representations
$signatureStarter->measurementUnits = PadesMeasurementUnits::CENTIMETERS;

// Set the signature policy. For this sample, we'll use the Lacuna Test PKI in order to accept our test certificate used
// above ("Pierre de Fermat"). This security context should be used FOR DEVELOPMENT PUPOSES ONLY. In production, you'll
// typically want one of the alternatives below
$signatureStarter->signaturePolicy = StandardSignaturePolicies::PADES_BASIC;
$signatureStarter->securityContext = '803517ad-3bbc-4169-b085-60053a8f6dbf';

// Alternative option: PAdES Basic with ICP-Brasil certificates
//$signatureStarter->signaturePolicy = StandardSignaturePolicies::PADES_BASIC_WITH_ICPBR_CERTS;

// Alternative option: add a ICP-Brasil timestamp to the signature
//$signatureStarter->signaturePolicy = StandardSignaturePolicies::PADES_T_WITH_ICPBR_CERTS;

// Alternative option: PAdES Basic with PKIs trusted by Windows
//$signatureStarter->signaturePolicy = StandardSignaturePolicies::PADES_BASIC;
//$signatureStarter->signaturePolicy = StandardSecurityContexts::WINDOWS_SERVER;

// Set the visual representation for the signature
$signatureStarter->setVisualRepresentation([

    'text' => [

        // The tags {{signerName}} and {{signerNationalId}} will be substituted according to the user's certificate
        // signerName -> full name of the signer
        // signerNationalId -> if the certificate is ICP-Brasil, contains the signer's CPF
        'text' => 'Signed by {{signerName}} ({{signerNationalId}})',
        // Specify that the signing time should also be rendered
        'includeSigningTime' => true,
        // Optionally set the horizontal alignment of the text ('Left' or 'Right'), if not set the default is Left
        'horizontalAlign' => 'Left',
        // Optionally set the container within the signature rectangle on which to place the text. By default, the
        // text can occupy the entire rectangle (how much of the rectangle the text will actually fill depends on the
        // length and font size). Below, we specify that the text should respect a right margin of 1.5 cm.
        'container' => [
            'left' => 0,
            'top' => 0,
            'right' => 1.5,
            'bottom' => 0
        ]
    ],
    'image' => [

        // We'll use as background the image content/PdfStamp.png
        'resource' => [
            'content' => base64_encode(getPdfStampContent()),
            'mimeType' => 'image/png'
        ],
        // Opacity is an integer from 0 to 100 (0 is completely transparent, 100 is completely opaque).
        'opacity' => 50,
        // Align the image to the right
        'horizontalAlign' => 'Right',
        // Align the image to the center
        'verticalAlign' => 'Center',

    ],
    // Position of the visual representation. We have encapsulated this code in a function to include several
    // possibilities depending on the argument passed to the function. Experiment changing the argument to see
    // different examples of signature positioning. Once you decide which is best for your case, you can place the
    // code directly here.
    'position' => getVisualRepresentationPosition(1)

]);

// If the user was redirected here by upload.php (signature with file uploaded by user), the "userfile" URL argument
// will contain the filename under the "app-data" folder. Otherwise (signature with server file), we'll sign a sample
// document.
$userfile = isset($_GET['userfile']) ? $_GET['userfile'] : null;
if (!empty($userfile)) {
    $signatureStarter->setPdfToSignFromPath("app-data/{$userfile}");
} else {
    $signatureStarter->setPdfToSignFromPath('content/SampleDocument.pdf');
}

/*
	Optionally, add marks to the PDF before signing. These differ from the signature visual representation in that
	they are actually changes done to the document prior to signing, not binded to any signature. Therefore, any number
	of marks can be added, for instance one per page, whereas there can only be one visual representation per signature.
	However, since the marks are in reality changes to the PDF, they can only be added to documents which have no previous
	signatures, otherwise such signatures would be made invalid by the changes to the document (see property
	PadesSignatureStarter.BypassMarksIfSigned). This problem does not occurr with signature visual representations.

	We have encapsulated this code in a method to include several possibilities depending on the argument passed.
	Experiment changing the argument to see different examples of PDF marks. Once you decide which is best for your case,
	you can place the code directly here.
*/
//array_push($signatureStarter->pdfMarks, PadesVisualElements::getPdfMark(1));

// Call the start() method, which initiates the signature. This yields the parameters for the signature using the
// certificate
$signatureParams = $signatureStarter->start();

// Perform the signature using the parameters returned by Rest PKI with the key extracted from PKCS #12
openssl_sign($signatureParams->toSignData, $signature, $certObj['pkey'], $signatureParams->openSslSignatureAlgorithm);

// Instantiate the PadesSignatureFinisher2 class, responsible for completing the signature process
$signatureFinisher = new PadesSignatureFinisher2(getRestPkiClient());

// Set the token
$signatureFinisher->token = $signatureParams->token;

// Set the signature
$signatureFinisher->setSignatureBinary($signature);

// Call the finish() method, which finalizes the signature process and returns a SignatureResult object
$signatureResult = $signatureFinisher->finish();

// The "certificate" property of the SignatureResult object contains information about the certificate used by the user
// to sign the file.
$signerCert = $signatureResult->certificate;

// At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
// store the PDF on a temporary folder publicly accessible and render a link to it.

$filename = uniqid() . ".pdf";
createAppData(); // make sure the "app-data" folder exists (util.php)

// The SignatureResult object has functions for writing the signature file to a local file (writeToFile()) and to get
// its raw contents (getContent()). For large files, use writeToFile() in order to avoid memory allocation issues.
$signatureResult->writeToFile("app-data/{$filename}");

?><!DOCTYPE html>
<html>
<head>
    <title>PAdES Signature</title>
    <?php include 'includes.php' // jQuery and other libs (used only to provide a better user experience, but NOT required to use the Web PKI component) ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely ?>

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
        <li><a href="open-pades-signature.php?userfile=<?= $filename ?>">Open/validate the signed file</a></li>
        <li><a href="pades-signature.php?userfile=<?= $filename ?>">Co-sign with another certificate</a></li>
    </ul>

</div>

</body>
</html>

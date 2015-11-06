<?php

/*
 * This file receives the form submission from pades-signature.php. We'll call REST PKI to complete the signature.
 */

// The file RestPki.php contains the helper classes to call the REST PKI API
require_once 'RestPki.php';

// The file util.php contains the function getRestPkiClient(), which gives us an instance of the RestPkiClient class
// initialized with the API access token
require_once 'util.php';

use Lacuna\PadesSignatureFinisher;

// Get the token for this signature (rendered in a hidden input field, see pades-signature.php)
$token = $_POST['token'];

// Instantiate the PadesSignatureFinisher class, responsible for completing the signature process
$signatureFinisher = new PadesSignatureFinisher(getRestPkiClient());

// Set the token
$signatureFinisher->setToken($token);

// Call the finish() method, which finalizes the signature process and returns the signed PDF
$signedPdf = $signatureFinisher->finish();

// Get information about the certificate used by the user to sign the file. This method must only be called after
// calling the finish() method.
$signerCert = $signatureFinisher->getCertificate();

// At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
// store the PDF on a temporary folder publicly accessible and render a link to it.

$id = uniqid();
$appDataPath = "app-data";
if (!file_exists($appDataPath)) {
	mkdir($appDataPath);
}
file_put_contents("{$appDataPath}/{$id}.pdf", $signedPdf);

?><!DOCTYPE html>
<html>
<head>
	<title>PAdES Signature</title>
	<?php include 'includes.php' // jQuery and other libs (for a sample without jQuery, see https://github.com/LacunaSoftware/RestPkiSamples/tree/master/PHP#barebones-sample) ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely ?>

<div class="container">

	<h2>PAdES Signature</h2>

	<p>File signed successfully! <a href="app-data/<?= $id ?>.pdf">Click here to download the signed file</a></p>

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
			</ul>
		</li>
	</ul>
	</p>

</div>

</body>
</html>
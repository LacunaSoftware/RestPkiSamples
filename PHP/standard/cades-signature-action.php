<?php

/*
 * This file receives the form submission from cades-signature.php. We'll call REST PKI to complete the signature.
 */

// The file RestPki.php contains the helper classes to call the REST PKI API
require_once 'RestPki.php';

// The file util.php contains the function getRestPkiClient(), which gives us an instance of the RestPkiClient class
// initialized with the API access token
require_once 'util.php';

use Lacuna\CadesSignatureFinisher;

// Get the token for this signature (rendered in a hidden input field, see cades-signature.php)
$token = $_POST['token'];

// Instantiate the CadesSignatureFinisher class, responsible for completing the signature process
$signatureFinisher = new CadesSignatureFinisher(getRestPkiClient());

// Set the token
$signatureFinisher->setToken($token);

// Call the finish() method, which finalizes the signature process and returns the CMS (p7s file) bytes
$cms = $signatureFinisher->finish();

// Get information about the certificate used by the user to sign the file. This method must only be called after
// calling the finish() method.
$signerCert = $signatureFinisher->getCertificate();

// At this point, you'd typically store the CMS on your database. For demonstration purposes, we'll
// store the CMS on a temporary folder publicly accessible and render a link to it.

createAppData(); // make sure the "app-data" folder exists (util.php)
$filename = uniqid() . ".p7s";
file_put_contents("app-data/{$filename}", $cms);

?><!DOCTYPE html>
<html>
<head>
	<title>PAdES Signature</title>
	<?php include 'includes.php' // jQuery and other libs (for a sample without jQuery, see https://github.com/LacunaSoftware/RestPkiSamples/tree/master/PHP#barebones-sample) ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely ?>

<div class="container">

	<h2>CAdES Signature</h2>

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
			</ul>
		</li>
	</ul>
	</p>

	<p><a href="cades-signature.php?cmsfile=<?= $filename ?>">Click here</a> to co-sign with another certificate</p>

</div>

</body>
</html>

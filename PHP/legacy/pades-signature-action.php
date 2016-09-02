<?php

/*
 * This file receives the form submission from pades-signature.php. We'll call REST PKI to complete the signature.
 */

// The file RestPkiLegacy.php contains the helper classes to call the REST PKI API for PHP 5.3+. Notice: if you're using
// PHP version 5.5 or greater, please use one of the other samples, which make better use of the extended capabilities
// of the newer versions of PHP - https://github.com/LacunaSoftware/RestPkiSamples/tree/master/PHP
require_once 'RestPkiLegacy.php';

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

$filename = uniqid() . ".pdf";
createAppData(); // make sure the "app-data" folder exists (util.php)
file_put_contents("app-data/{$filename}", $signedPdf);

?><!DOCTYPE html>
<html>
<head>
	<title>PAdES Signature</title>
	<?php include 'includes.php' // jQuery and other libs (for a sample without jQuery, see https://github.com/LacunaSoftware/RestPkiSamples/tree/master/PHP) ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely ?>

<div class="container">

	<h2>PAdES Signature</h2>

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
				</ul>
			</li>
		</ul>
	</p>

	<h3>Actions:</h3>
	<ul>
		<li><a href="app-data/<?php echo $filename ?>">Download the signed file</a></li>
		<li><a href="open-pades-signature.php?userfile=<?php echo $filename ?>">Open/validate the signed file</a></li>
		<li><a href="pades-signature.php?userfile=<?php echo $filename ?>">Co-sign with another certificate</a></li>
	</ul>

</div>

</body>
</html>

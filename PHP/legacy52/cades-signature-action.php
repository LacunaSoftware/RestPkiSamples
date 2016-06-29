<?php

/*
 * This file receives the form submission from cades-signature.php. We'll call REST PKI to complete the signature.
 */

// The file RestPkiLegacy52.php contains the helper classes to call the REST PKI API for PHP 5.2+. Notice: if you're
// using PHP version 5.3 or greater, please use one of the other samples, which make better use of the extended
// capabilities of the newer versions of PHP - https://github.com/LacunaSoftware/RestPkiSamples/tree/master/PHP
require_once 'RestPkiLegacy52.php';

// The file util.php contains the function getRestPkiClient(), which gives us an instance of the LacunaRestPkiClient
// class initialized with the API access token
require_once 'util.php';


// Get the token for this signature (rendered in a hidden input field, see cades-signature.php)
$token = $_POST['token'];

// Instantiate the LacunaCadesSignatureFinisher class, responsible for completing the signature process
$signatureFinisher = new LacunaCadesSignatureFinisher(getRestPkiClient());

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

?>

<!DOCTYPE html>
<html>
<head>
	<title>CAdES Signature</title>
	<?php include 'includes.php' // jQuery and other libs (for a sample without jQuery, see https://github.com/LacunaSoftware/RestPkiSamples/tree/master/PHP#barebones-sample) ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely ?>

<div class="container">

	<h2>CAdES Signature</h2>

	<p>File signed successfully! <a href="app-data/<?php echo $filename; ?>">Click here to download the signed file</a></p>

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

	<p><a href="cades-signature.php?cmsfile=<?php echo $filename; ?>">Click here</a> to co-sign with another certificate</p>

</div>

</body>
</html>

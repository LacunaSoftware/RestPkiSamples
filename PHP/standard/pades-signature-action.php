<?php

require_once 'RestPki.php';
require_once 'util.php';

use Lacuna\PadesSignatureFinisher;

$token = $_POST['token'];

$signatureFinisher = new PadesSignatureFinisher(getRestPkiClient());
$signatureFinisher->setToken($token);
$signedPdf = $signatureFinisher->finish();
$signerCert = $signatureFinisher->getCertificate();

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
	<?php include 'includes.php' ?>
</head>
<body>

<?php include 'menu.php' ?>

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

<!DOCTYPE html>
<html>
<head>
	<title>REST PKI Samples</title>
</head>
<body>

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

?>

	<p>File signed successfully!<a href="app-data/<?php echo $id ?>.pdf">click here</a> to download the signed file</p>

	<p>
		Signer information:
		<ul>
			<li>Subject: <?php echo $signerCert->subjectName->commonName ?></li>
			<li>Email: <?php echo $signerCert->emailAddress ?></li>
			<li>
				ICP-Brasil fields
				<ul>
					<li>Tipo de certificado: <?php echo $signerCert->pkiBrazil->certificateType ?></li>
					<li>CPF: <?php echo $signerCert->pkiBrazil->cpf ?></li>
					<li>Responsavel: <?php echo $signerCert->pkiBrazil->responsavel ?></li>
					<li>Empresa: <?php echo $signerCert->pkiBrazil->companyName ?></li>
					<li>CNPJ: <?php echo $signerCert->pkiBrazil->cnpj ?></li>
				</ul>
			</li>
		</ul>
	</p>

</body>
</html>

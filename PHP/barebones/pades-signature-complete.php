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

	$id = uniqid();
	$appDataPath = "app-data";
	if (!file_exists($appDataPath)) {
		mkdir($appDataPath);
	}
	file_put_contents("{$appDataPath}/{$id}.pdf", $signedPdf);

	echo "<p>File signed successfully! <a href='app-data/{$id}.pdf'>click here</a> to download the signed file</p>";

?>

</body>
</html>

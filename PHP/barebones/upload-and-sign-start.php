<?php

	require_once 'RestPki.php';
	require_once 'util.php';

	if (!array_key_exists('userfile', $_FILES)) {
		die('File upload error');
	}

	$file = $_FILES['userfile'];
	if ($file['size'] > 10485760) {
		die('File too large');
	}

	$uploadsPath = "uploads";
	if (!file_exists($uploadsPath)) {
		mkdir($uploadsPath);
	}
	$id = uniqid();
	$targetPath = "{$uploadsPath}/{$id}";
	if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
		die('File upload error');
	}

	use Lacuna\PadesSignatureStarter;
	use Lacuna\PadesVisualPositioningPresets;
	use Lacuna\StandardSecurityContexts;
	use Lacuna\StandardSignaturePolicies;

	$signatureStarter = new PadesSignatureStarter(getRestPkiClient());
	$signatureStarter->setPdfToSignContent(file_get_contents($targetPath));
	$signatureStarter->setSignaturePolicy(StandardSignaturePolicies::PADES_BASIC);
	$signatureStarter->setSecurityContext(StandardSecurityContexts::PKI_BRAZIL);
	$signatureStarter->setVisualRepresentation([
		'text' => [
			'text' => 'Signed by {{signerName}}',
			'includeSigningTime' => true
		],
		'image' => [
			'resource' => [
				'content' => base64_encode(file_get_contents('content/PdfStamp.png')),
				'mimeType' => 'image/png'
			],
			'opacity' => 50,
			'horizontalAlign' => 'Right'
		],
		'position' => PadesVisualPositioningPresets::getFootnote(getRestPkiClient())
	]);

	$token = $signatureStarter->startWithWebPki();
	setExpiredPage();

?><!DOCTYPE html>
<html>
<head>
	<title>REST PKI Samples</title>
	<script src="content/js/lacuna-web-pki-2.2.2.js"></script>
	<script src="content/js/web-pki-util.js"></script>
</head>
<body>

<script>
	function sign() {
		var selectedCertThumbprint = getSelectedCertificateThumbprint();
		if (selectedCertThumbprint == null) {
			alert('Please select a certificate!');
			return;
		}
		pki.signWithRestPki({
			token: '<?php echo $token ?>',
			thumbprint: selectedCertThumbprint
		}).success(function () {
			document.getElementById('signForm').submit();
		});
	}
</script>

<h2>Upload and Sign</h2>
<h3>2. Choose a certificate and sign the file</h3>

<form id="signForm" action="pades-signature-complete.php" method="POST">
	<input type="hidden" name="token" value="<?php echo $token ?>">
	<p>Choose a certificate: <select id="certificateSelect"></select></p>
	<p><button type="button" onclick="sign()">Sign File</button></p>
</form>

</body>
</html>

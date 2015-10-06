<?php
	
	require_once 'RestPki.php';
	require_once 'util.php';

	use Lacuna\PadesSignatureStarter;
	use Lacuna\PadesVisualPositioningPresets;
	use Lacuna\StandardSecurityContexts;
	use Lacuna\StandardSignaturePolicies;

	$signatureStarter = new PadesSignatureStarter(getRestPkiClient());
	$signatureStarter->setPdfToSignContent(file_get_contents("content/SampleDocument.pdf"));
	$signatureStarter->setSignaturePolicy(StandardSignaturePolicies::PADES_BASIC);
	$signatureStarter->setSecurityContext(StandardSecurityContexts::PKI_BRAZIL);
	$signatureStarter->setVisualRepresentation([
		'text' => [
			'text' => 'Signed by {{signerName}} ({{signerNationalId}})',
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

<h2>PAdES Signature</h2>

<form id="signForm" action="pades-signature-complete.php" method="POST">
	<input type="hidden" name="token" value="<?php echo $token ?>">
	<p>File to sign: You'll be signing <a href='content/SampleDocument.pdf'>this sample document</a>.</p>
	<p>Choose a certificate: <select id="certificateSelect"></select></p>
	<p><button type="button" onclick="sign()">Sign File</button></p>
</form>

</body>
</html>

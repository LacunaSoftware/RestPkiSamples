<?php

	require_once 'RestPki.php';
	require_once 'util.php';

	$auth = getRestPkiClient()->getAuthentication();
	$token = $auth->startWithWebPki(\Lacuna\StandardSecurityContexts::PKI_BRAZIL);
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
	function signIn() {
		var selectedCertThumbprint = getSelectedCertificateThumbprint();
		if (selectedCertThumbprint == null) {
			alert('Please select a certificate!');
			return;
		}
		pki.signWithRestPki({
			token: '<?php echo $token ?>',
			thumbprint: selectedCertThumbprint
		}).success(function () {
			document.getElementById('authForm').submit();
		});
	}
</script>

<h2>Authentication</h2>

<form id="authForm" action="authentication-action.php" method="POST">
	<input type="hidden" name="token" value="<?php echo $token ?>">
	<p>Choose a certificate: <select id="certificateSelect"></select></p>
	<p><button type="button" onclick="signIn()">Sign In</button></p>
</form>

</body>
</html>

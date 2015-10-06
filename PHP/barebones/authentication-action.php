<!DOCTYPE html>
<html>
<head>
	<title>REST PKI Samples</title>
</head>
<body>

<?php

	require_once 'RestPki.php';
	require_once 'util.php';

	$token = $_POST['token'];
	$auth = getRestPkiClient()->getAuthentication();
	$vr = $auth->completeWithWebPki($token);

	if ($vr->isValid()) {

		$userCert = $auth->getCertificate();
		echo '<h2>Authentication successful</h2>';
		echo '<p>';
		echo "Welcome, {$userCert->subjectName->commonName}!";
		if (!empty($userCert->emailAddress)) {
			echo " Your email address is {$userCert->emailAddress}";
		}
		if (!empty($userCert->pkiBrazil->cpf)) {
			echo " and your CPF is {$userCert->pkiBrazil->cpf}";
		}
		echo '</p>';

	} else {

		$vrHtml = $vr;
		$vrHtml = str_replace("\n", '<br/>', $vrHtml);
		$vrHtml = str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', $vrHtml);
		echo '<h2>Validation failed</h2>';
		echo "<p>{$vrHtml}</p>";

	}

?>

</body>
</html>

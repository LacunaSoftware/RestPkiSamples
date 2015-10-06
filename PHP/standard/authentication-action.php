<?php

require_once 'RestPki.php';
require_once 'util.php';

$token = $_POST['token'];
$auth = getRestPkiClient()->getAuthentication();
$vr = $auth->completeWithWebPki($token);

?><!DOCTYPE html>
<html>
<head>
	<title>Authentication</title>
	<?php include 'includes.php' ?>
</head>
<body>

<?php include 'menu.php' ?>

<div class="container">

	<?php

	if ($vr->isValid()) {

		$cert = $auth->getCertificate();

		?>

		<h2>Authentication successful</h2>

		<p>
			User certificate information:
			<ul>
				<li>Subject: <?= $cert->subjectName->commonName ?></li>
				<li>Email: <?= $cert->emailAddress ?></li>
				<li>
					ICP-Brasil fields
					<ul>
						<li>Tipo de certificado: <?= $cert->pkiBrazil->certificateType ?></li>
						<li>CPF: <?= $cert->pkiBrazil->cpf ?></li>
						<li>Responsavel: <?= $cert->pkiBrazil->responsavel ?></li>
						<li>Empresa: <?= $cert->pkiBrazil->companyName ?></li>
						<li>CNPJ: <?= $cert->pkiBrazil->cnpj ?></li>
					</ul>
				</li>
			</ul>
		</p>

		<?php

	} else {

		$vrHtml = $vr;
		$vrHtml = str_replace("\n", '<br/>', $vrHtml);
		$vrHtml = str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', $vrHtml);
		echo '<h2>Validation failed</h2>';
		echo "<p>{$vrHtml}</p>";

	}

	?>

</div>

</body>
</html>

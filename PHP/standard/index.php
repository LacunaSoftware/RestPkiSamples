<!DOCTYPE html>
<html>
<head>
	<title>REST PKI Samples</title>
	<?php include 'includes.php' ?>
</head>
<body>

    <?php include 'menu.php' ?>

	<div class="container">
		
		<h2>REST PKI Samples</h2>
		Choose one of the following:
		<ul>
			<li><a href="authentication.php">Authentication with digital certificate</a></li>
			<li>
				Create a PAdES signature
				<ul>
					<li><a href="pades-signature.php">With a file already on server</a></li>
					<li><a href="upload.php?goto=pades-signature">With a file uploaded by user</a></li>
				</ul>
			</li>
			<li>
				Create a CAdES signature
				<ul>
					<li><a href="cades-signature.php">With a file already on server</a></li>
					<li><a href="upload.php?goto=cades-signature">With a file uploaded by user</a></li>
				</ul>
			</li>
			<li>
				Create a XML signature
				<ul>
					<li><a href="xml-full-signature.php">Sign an entire XML (enveloped signature)</a></li>
					<li><a href="xml-element-signature.php">Sign an element inside of a XML</a></li>
				</ul>
			</li>
		</ul>

	</div>
</body>
</html>

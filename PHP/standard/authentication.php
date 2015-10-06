<?php
	
	/*
	  This is the page for authentication. Actual logic can be found at:
	  - Client-side: content/js/app/authentication.js
	  - Server-side: api/authentication.php
	 */

	// We'll call the function getRestPkiClient() just to make sure the API access token was set. You can remove this in your application.
	require_once 'api/util.php';
	getRestPkiClient();
	
?><!DOCTYPE html>
<html>
<head>
	<?php include 'head.php' ?>
</head>
<body>

<?php include 'top.php' ?>

<div class="container">

	<h2>Authentication</h2>

	<form>
		<div class="form-group">
			<label for="certificateSelect">Choose a certificate</label>
			<select id="certificateSelect" class="form-control"></select>
		</div>
		<button id="signInButton" type="button" class="btn btn-primary">Sign In</button>
		<button id="refreshButton" type="button" class="btn btn-default">Refresh Certificates</button>
	</form>

	<fieldset id="validationResultsPanel" style="display: none;">
		<legend>Validation Results</legend>
		<textarea readonly="true" rows="25" style="width: 100%"></textarea>
	</fieldset>

	<script src="content/js/lacuna-web-pki-2.2.2.js"></script>
	<script src="content/js/app/authentication.js"></script>

</div>
</body>
</html>

<?php

/*
 * This file allows the user to upload a file to be signed. Once the file is uploaded, we save it to the
 * uplods/ folder and redirect the user to the pades-signature.php file passing the filename on the "userfile" URL
 * argument.
 */

if (isset($_FILES['userfile'])) {

	// Process the file uploaded

	$file = $_FILES['userfile'];
	if ($file['size'] > 10485760) { // 10MB
		die('File too large');
	}

	// Create the uploads/ folder if it does not exist
	$uploadsPath = "uploads";
	if (!file_exists($uploadsPath)) {
		mkdir($uploadsPath);
	}
	// Generate a unique filename
	$id = uniqid();
	// Move the file to the uploads/ folder with the unique filename
	$targetPath = "{$uploadsPath}/{$id}.pdf";
	if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
		die('File upload error');
	}

	// Redirect the user to the PAdES signature page, passing the name of the file as a URL argument
	header("Location: pades-signature.php?userfile={$id}", TRUE, 302);
	exit;

}

?><!DOCTYPE html>
<html>
<head>
	<title>PAdES Signature (user file)</title>
	<?php include 'includes.php' // jQuery and other libs (for a sample without jQuery, see https://github.com/LacunaSoftware/RestPkiSamples/tree/master/PHP) ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely ?>

<div class="container">

	<h2>Upload a PDF</h2>

	<form method="post" enctype="multipart/form-data">
		<?php /* MAX_FILE_SIZE = 10MB (see http://php.net/manual/en/features.file-upload.post-method.php) */ ?>
		<input type="hidden" name="MAX_FILE_SIZE" value="10485760"/>
		<p>Select file: <input type="file" name="userfile"></p>
		<p><input type="submit" value="Upload" name="submit"></p>
	</form>

</div>

</body>
</html>

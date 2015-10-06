<!DOCTYPE html>
<html>
<head>
	<title>REST PKI Samples</title>
</head>
<body>

<h2>Upload and Sign</h2>
<h3>1. Upload a file</h3>

<form action="upload-and-sign-start.php" method="post" enctype="multipart/form-data">
	<?php /* MAX_FILE_SIZE = 10MB (see http://php.net/manual/en/features.file-upload.post-method.php) */ ?>
	<input type="hidden" name="MAX_FILE_SIZE" value="10485760" />
	<p>Select file to sign: <input type="file" name="userfile"></p>
	<p><input type="submit" value="Upload" name="submit"></p>
</form>

</body>
</html>

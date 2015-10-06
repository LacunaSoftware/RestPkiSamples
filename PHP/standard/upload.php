<!DOCTYPE html>
<html>
<head>
	<title>Authentication</title>
	<?php include 'includes.php' ?>
</head>
<body>

<?php include 'menu.php' ?>

<div class="container">

	<h2>Upload a PDF</h2>

	<form action="upload-action.php" method="post" enctype="multipart/form-data">
		<?php /* MAX_FILE_SIZE = 10MB (see http://php.net/manual/en/features.file-upload.post-method.php) */ ?>
		<input type="hidden" name="MAX_FILE_SIZE" value="10485760"/>
		<p>Select file: <input type="file" name="userfile"></p>
		<p><input type="submit" value="Upload" name="submit"></p>
	</form>

</div>

</body>
</html>

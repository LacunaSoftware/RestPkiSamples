<?php

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
$targetPath = "{$uploadsPath}/{$id}.pdf";
if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
	die('File upload error');
}

header("Location: pades-signature.php?file={$id}", TRUE, 302);

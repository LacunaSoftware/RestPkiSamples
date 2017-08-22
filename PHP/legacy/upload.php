<?php

/*
 * This file allows the user to upload a file to be signed. Once the file is uploaded, we save it to the
 * "app-data" folder and redirect the user to the pades-signature.php file passing the filename on the "userfile" URL
 * argument.
 */

require_once 'util.php';

if (isset($_FILES['userfile'])) {

    // Process the file uploaded

    $file = $_FILES['userfile'];
    if ($file['size'] > 10485760) { // 10MB
        die('File too large');
    }
    $filenameParts = explode('.', $file['name']);
    $fileExt = end($filenameParts);

    // Generate a unique filename
    $filename = uniqid() . ".{$fileExt}";

    // Move the file to the "app-data" folder with the unique filename
    createAppData(); // make sure the "app-data" folder exists (util.php)
    if (!move_uploaded_file($file['tmp_name'], "app-data/{$filename}")) {
        die('File upload error');
    }

    // Redirect the user to the PAdES signature page, passing the name of the file as a URL argument
    header("Location: " . $_GET['goto'] . ".php?userfile={$filename}", true, 302);
    exit;

}

?><!DOCTYPE html>
<html>
<head>
    <title>Upload a file</title>
    <?php include 'includes.php' // jQuery and other libs (for a sample without jQuery, see https://github.com/LacunaSoftware/RestPkiSamples/tree/master/PHP) ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely ?>

<div class="container">

    <h2>Upload a file</h2>

    <form method="post" enctype="multipart/form-data">
        <?php /* MAX_FILE_SIZE = 10MB (see http://php.net/manual/en/features.file-upload.post-method.php) */ ?>
        <input type="hidden" name="MAX_FILE_SIZE" value="10485760"/>
        <p>Select file: <input type="file" name="userfile"></p>
        <p><input type="submit" value="Upload" name="submit"></p>
    </form>

</div>

</body>
</html>

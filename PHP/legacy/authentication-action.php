<?php

/*
 * This file receives the form submission from authentication.php. We'll call REST PKI to validate the authentication.
 */

// The file RestPkiLegacy.php contains the helper classes to call the REST PKI API for PHP 5.3+. Notice: if you're using
// PHP version 5.5 or greater, please use one of the other samples, which make better use of the extended capabilities
// of the newer versions of PHP - https://github.com/LacunaSoftware/RestPkiSamples/tree/master/PHP
require_once 'RestPkiLegacy.php';

// The file util.php contains the function getRestPkiClient(), which gives us an instance of the RestPkiClient class
// initialized with the API access token
require_once 'util.php';

// Get the token for this authentication (rendered in a hidden input field, see authentication.php)
$token = $_POST['token'];

// Get an instance of the Authentication class
$auth = getRestPkiClient()->getAuthentication();

// Call the completeWithWebPki() method with the token, which finalizes the authentication process. The call yields a
// ValidationResults which denotes whether the authentication was successful or not (we'll use it to render the page
// accordingly, see below).
$vr = $auth->completeWithWebPki($token);

if ($vr->isValid()) {
    $userCert = $auth->getCertificate();
    // At this point, you have assurance that the certificate is valid according to the SecurityContext specified on the
    // file authentication.php and that the user is indeed the certificate's subject. Now, you'd typically query your
    // database for a user that matches one of the certificate's fields, such as $userCert->emailAddress or
    // $userCert->pkiBrazil->cpf (the actual field to be used as key depends on your application's business logic) and
    // set the user as authenticated with whatever web security framework your application uses. For demonstration
    // purposes, we'll just render the user's certificate information.
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Authentication</title>
    <meta charset="utf-8">
    <?php include 'includes.php' // jQuery and other libs (for a sample without jQuery, see
    // https://github.com/LacunaSoftware/RestPkiSamples/tree/master/PHP) ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely ?>

<div class="container">

    <?php

    // We'll render different contents depending on whether the authentication succeeded or not
    if ($vr->isValid()) {

        ?>

        <h2>Authentication successful</h2>

        <p>
            User certificate information:
            <ul>
                <li>Subject: <?php echo $userCert->subjectName->commonName; ?></li>
                <li>Email: <?php echo $userCert->emailAddress; ?></li>
                <li>
                    ICP-Brasil fields
                    <ul>
                        <li>Tipo de certificado: <?php echo $userCert->pkiBrazil->certificateType; ?></li>
                        <li>CPF: <?php echo $userCert->pkiBrazil->cpf; ?></li>
                        <li>Responsavel: <?php echo $userCert->pkiBrazil->responsavel; ?></li>
                        <li>Empresa: <?php echo $userCert->pkiBrazil->companyName; ?></li>
                        <li>CNPJ: <?php echo $userCert->pkiBrazil->cnpj; ?></li>
                        <li>
                            RG: <?php echo $userCert->pkiBrazil->rgNumero . " " . $userCert->pkiBrazil->rgEmissor . " " . $userCert->pkiBrazil->rgEmissorUF ?></li>
                        <li>OAB: <?php echo $userCert->pkiBrazil->oabNumero . " " . $userCert->pkiBrazil->oabUF ?></li>
                    </ul>
                </li>
            </ul>
        </p>

        <?php

    } else {

        // The $vr object can be used as a string, but the string contains tabs and new line characters for formatting,
        // which we'll convert to <br>'s and &nbsp;'s.
        $vrHtml = $vr;
        $vrHtml = str_replace("\n", '<br/>', $vrHtml);
        $vrHtml = str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', $vrHtml);

        ?>

        <h2>Authentication Failed</h2>
        <p><?php echo $vrHtml; ?></p>
        <p><a href="authentication.php" class="btn btn-primary">Try again</a></p>

        <?php

    }

    ?>

</div>

</body>
</html>

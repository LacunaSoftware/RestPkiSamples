<?php

/*
 * This file initiates an authentication with REST PKI and renders the authentication page. The form is posted to
 * another file, authentication-action.php, which calls REST PKI again to validate the data received.
 */

require __DIR__ . '/vendor/autoload.php';

use Lacuna\RestPki\Authentication;

// Get an instance of the Authentication class (see util.php).
$auth = new Authentication(getRestPkiClient());

// Call the startWithWebPki() method, which initiates the authentication. This yields the "token", a 22-character
// case-sensitive URL-safe string, which represents this authentication process. We'll use this value to call the
// signWithRestPki() method on the Web PKI component (see javascript below) and also to call the completeWithWebPki()
// method on the file authentication-action.php. This should not be mistaken with the API access token. We have
// encapsulated the security context choice on util.php.
$token = $auth->startWithWebPki(getSecurityContextId());

// The token acquired above can only be used for a single authentication. In order to retry authenticating it is
// necessary to get a new token. This can be a problem if the user uses the back button of the browser, since the
// browser might show a cached page that we rendered previously, with a now stale token. To prevent this from happening,
// we call the function setExpiredPage(), located in util.php, which sets HTTP headers to prevent caching of the page.
setExpiredPage();

?><!DOCTYPE html>
<html>
<head>
    <title>Authentication</title>
    <?php include 'includes.php' // jQuery and other libs (used only to provide a better user experience, but NOT required to use the Web PKI component). ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely. ?>

<div class="container">

    <h2>Authentication</h2>

    <?php // notice that we'll post to a different PHP file. ?>
    <form id="authForm" action="authentication-action.php" method="POST">

        <?php // render the $token in a hidden input field. ?>
        <input type="hidden" name="token" value="<?= $token ?>">

        <?php
        // Render a select (combo box) to list the user's certificates. For now it will be empty, we'll populate it
        // later on (see signature-form.js).
        ?>
        <div class="form-group">
            <label for="certificateSelect">Choose a certificate</label>
            <select id="certificateSelect" class="form-control"></select>
        </div>

        <?php
        // Action buttons. Notice that the "Sign In" button is NOT a submit button. When the user clicks the button,
        // we must first use the Web PKI component to perform the client-side computation necessary and only when
        // that computation is finished we'll submit the form programmatically (see signature-form.js).
        ?>
        <button id="signInButton" type="button" class="btn btn-primary">Sign In</button>
        <button id="refreshButton" type="button" class="btn btn-default">Refresh Certificates</button>
    </form>

</div>

<?php
// The file below contains the JS lib for accessing the Web PKI component. For more information, see:
// https://webpki.lacunasoftware.com/#/Documentation
?>
<script src="content/js/lacuna-web-pki-2.9.0.js"></script>

<?php
// The file below contains the logic for calling the Web PKI component. It is only an example, feel free to alter it
// to meet your application's needs. You can also bring the code into the javascript block below if you prefer.
?>
<script src="content/js/signature-form.js"></script>
<script>
    $(document).ready(function () {
        // Once the page is ready, we call the init() function on the javascript code (see signature-form.js).
        signatureForm.init({
            token: '<?= $token ?>',                     // The token acquired from REST PKI.
            form: $('#authForm'),                       // The form that should be submitted when the operation is complete.
            certificateSelect: $('#certificateSelect'), // The <select> element (combo box) to list the certificates.
            refreshButton: $('#refreshButton'),         // The "refresh" button.
            signButton: $('#signInButton')              // The button that initiates the operation.
        });
    });
</script>

</body>
</html>

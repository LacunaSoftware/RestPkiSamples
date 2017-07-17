<?php

/*
 * This file initiates a CAdES signature using REST PKI and renders the signature page. The form is posted to
 * another file, cades-signature-action.php, which calls REST PKI again to complete the signature.
 *
 * All CAdES signature examples converge to this action, but with different URL arguments:
 *
 * 1. Signature with a server file               : no arguments filled
 * 2. Signature with a file uploaded by the user : "userfile" filled
 * 3. Co-signature of a previously signed CMS    : "cmsfile" filled
 */

require __DIR__ . '/vendor/autoload.php';

use Lacuna\RestPki\CadesSignatureStarter;
use Lacuna\RestPki\StandardSignaturePolicies;

$userfile = isset($_GET['userfile']) ? $_GET['userfile'] : null;
$cmsfile = isset($_GET['cmsfile']) ? $_GET['cmsfile'] : null;

// Instantiate the CadesSignatureStarter class, responsible for receiving the signature elements and start the signature
// process
$signatureStarter = new CadesSignatureStarter(getRestPkiClient());

if (!empty($userfile)) {

    // If the URL argument "userfile" is filled, it means the user was redirected here by the file upload.php (signature
    // with file uploaded by user). We'll set the path of the file to be signed, which was saved in the "app-data" folder
    // by upload.php
    $signatureStarter->setFileToSignFromPath("app-data/{$userfile}");

} elseif (!empty($cmsfile)) {

    /*
     * If the URL argument "cmsfile" is filled, the user has asked to co-sign a previously signed CMS. We'll set the
     * path to the CMS to be co-signed, which was previously saved in the "app-data" folder by the file
     * cades-signature-action.php. Note two important things:
     *
     * 1. The CMS to be co-signed must be set using the method "setCmsToSign" or "setCmsFileToSign", not the method
     *    "setContentToSign" nor "setFileToSign".
     *
     * 2. Since we're creating CMSs with encapsulated content (see call to setEncapsulateContent below), we don't need
     *    to set the content to be signed, REST PKI will get the content from the CMS being co-signed.
     */
    $signatureStarter->setCmsToCoSignFromPath("app-data/{$cmsfile}");

} else {

    // If both userfile and cmsfile are null, this is the "signature with server file" case. We'll set the path to
    // the sample document.
    $signatureStarter->setFileToSignFromPath('content/SampleDocument.pdf');

}


// Set the signature policy
$signatureStarter->signaturePolicy = StandardSignaturePolicies::CADES_ICPBR_ADR_BASICA;

// Optionally, set a SecurityContext to be used to determine trust in the certificate chain
//$signatureStarter->setSecurityContext(\Lacuna\StandardSecurityContexts::PKI_BRAZIL);
// Note: Depending on the signature policy chosen above, setting the security context may be mandatory (this is not
// the case for ICP-Brasil policies, which will automatically use the PKI_BRAZIL security context if none is passed)

// Optionally, set whether the content should be encapsulated in the resulting CMS. If this parameter is omitted,
// the following rules apply:
// - If no CmsToSign is given, the resulting CMS will include the content
// - If a CmsToCoSign is given, the resulting CMS will include the content if and only if the CmsToCoSign also includes
//   the content
$signatureStarter->encapsulateContent = true;

// Call the startWithWebPki() method, which initiates the signature. This yields the token, a 43-character
// case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
// signWithRestPki() method on the Web PKI component (see javascript below) and also to complete the signature after
// the form is submitted (see file pades-signature-action.php). This should not be mistaken with the API access token.
$token = $signatureStarter->startWithWebPki();

// The token acquired above can only be used for a single signature attempt. In order to retry the signature it is
// necessary to get a new token. This can be a problem if the user uses the back button of the browser, since the
// browser might show a cached page that we rendered previously, with a now stale token. To prevent this from happening,
// we call the function setExpiredPage(), located in util.php, which sets HTTP headers to prevent caching of the page.
setExpiredPage();

?><!DOCTYPE html>
<html>
<head>
    <title>CAdES Signature</title>
    <?php include 'includes.php' // jQuery and other libs (used only to provide a better user experience, but NOT required to use the Web PKI component) ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely ?>

<div class="container">

    <h2>CAdES Signature</h2>

    <?php // notice that we'll post to a different PHP file ?>
    <form id="signForm" action="cades-signature-action.php" method="POST">

        <?php // render the $token in a hidden input field ?>
        <input type="hidden" name="token" value="<?= $token ?>">

        <div class="form-group">
            <label>File to sign</label>
            <?php if (!empty($userfile)) { ?>
                <p>You are signing <a href='app-data/<?= $userfile ?>'>this document</a>.</p>
            <?php } elseif (!empty($cmsfile)) { ?>
                <p>You are co-signing <a href='app-data/<?= $cmsfile ?>'>this CMS</a>.</p>
            <?php } else { ?>
                <p>You are signing <a href='content/SampleDocument.pdf'>this sample document</a>.</p>
            <?php } ?>
        </div>

        <?php
        // Render a select (combo box) to list the user's certificates. For now it will be empty, we'll populate it
        // later on (see javascript below).
        ?>
        <div class="form-group">
            <label for="certificateSelect">Choose a certificate</label>
            <select id="certificateSelect" class="form-control"></select>
        </div>

        <?php
        // Action buttons. Notice that the "Sign File" button is NOT a submit button. When the user clicks the button,
        // we must first use the Web PKI component to perform the client-side computation necessary and only when
        // that computation is finished we'll submit the form programmatically (see javascript below).
        ?>
        <button id="signButton" type="button" class="btn btn-primary">Sign File</button>
        <button id="refreshButton" type="button" class="btn btn-default">Refresh Certificates</button>
    </form>

</div>

<?php
// The file below contains the JS lib for accessing the Web PKI component. For more information, see:
// https://webpki.lacunasoftware.com/#/Documentation
?>
<script src="content/js/lacuna-web-pki-2.6.1.js"></script>

<?php
// The file below contains the logic for calling the Web PKI component. It is only an example, feel free to alter it
// to meet your application's needs. You can also bring the code into the javascript block below if you prefer.
?>
<script src="content/js/signature-form.js"></script>
<script>
    $(document).ready(function () {
        // Once the page is ready, we call the init() function on the javascript code (see signature-form.js)
        signatureForm.init({
            token: '<?= $token ?>',                     // token acquired from REST PKI
            form: $('#signForm'),                       // the form that should be submitted when the operation is complete
            certificateSelect: $('#certificateSelect'), // the select element (combo box) to list the certificates
            refreshButton: $('#refreshButton'),         // the "refresh" button
            signButton: $('#signButton')                // the button that initiates the operation
        });
    });
</script>

</body>
</html>

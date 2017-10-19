<?php

// The file RestPkiLegacy.php contains the helper classes to call the REST PKI API for PHP 5.3+. Notice: if you're using
// PHP version 5.5 or greater, please use one of the other samples, which make better use of the extended capabilities
// of the newer versions of PHP - https://github.com/LacunaSoftware/RestPkiSamples/tree/master/PHP
require_once 'RestPkiLegacy.php';

// The file util.php contains the function getRestPkiClient(), which gives us an instance of the RestPkiClient class
// initialized with the API access token
require_once 'util.php';

// The file storage-mock.php contains the mock functions used to store and to retrieve signature codes.
require_once 'storage-mock.php';

use Lacuna\PadesSignatureExplorer;
use Lacuna\StandardSignaturePolicies;
use Lacuna\StandardSecurityContexts;

// Get document ID from query string
$formattedCode = isset($_GET['c']) ? $_GET['c'] : null;
if (!isset($formattedCode)) {
    throw new \Exception("No code was provided");
}

// On printer-friendly-version.php, we stored the unformatted version of the verification code (without hyphens) but
// used the formatted version (with hyphens) on the printer-friendly PDF. Now, we remove the hyphen before looking it
// up.
$verificationCode = parseVerificationCode($formattedCode);

// Get document associated with verification code
$fileId = lookupVerificationCode($verificationCode);
if ($fileId == null) {
    // Invalid code given!
    // Small delay to slow down brute-force attacks (if you want to be extra careful you might want to add a CAPTCHA to
    // the process).
    sleep(2);

    // Inform that the file was not found
    die('File not found');
}

// Open and validate signature with Rest PKI
$client = getRestPkiClient();
$sigExplorer = new PadesSignatureExplorer($client);
$sigExplorer->setValidate(true);
$sigExplorer->setDefaultSignaturePolicyid(StandardSignaturePolicies::PADES_BASIC);
$sigExplorer->setSecurityContextId(StandardSecurityContexts::PKI_BRAZIL);
$sigExplorer->setSignatureFile('app-data/' . $fileId);

$signature = $sigExplorer->open();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Checking code signature</title>
    <meta charset="utf-8">
    <?php include 'includes.php' // jQuery and other libs (used only to provide a better user experience, but NOT
    // required to use the Web PKI component) ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely ?>

<div class="container">

    <h2>Check signatures on printer-friendly PDF</h2>

    <h3>The given file contains <?php echo count($signature->signers) ?> signatures:</h3>

    <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">

        <?php for ($i = 0; $i < count($signature->signers); $i++) {

            $signer = $signature->signers[$i];
            $collapseId = "signer_" . $i . "_collapse";
            $headingId = "signer_" . $i . "_heading";

            ?>

            <div class="panel panel-default">
                <div class="panel-heading" role="tab" id="<?php echo $headingId ?>">
                    <h4 class="panel-title">
                        <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                           href="#<?php echo $collapseId ?>" aria-expanded="true" aria-controls="<?php echo $collapseId ?>">
                            <?php echo $signer->certificate->subjectName->commonName ?>
                            <?php if ($signer->validationResults != null) { ?>
                                <text>-</text>
                                <?php if ($signer->validationResults->isValid()) { ?>
                                    <span style="color: green; font-weight: bold;">valid</span>
                                <?php } else { ?>
                                    <span style="color: red; font-weight: bold;">invalid</span>
                                <?php } ?>
                            <?php } ?>
                        </a>
                    </h4>
                </div>
                <div id="<?php echo $collapseId ?>" class="panel-collapse collapse" role="tabpanel"
                     aria-labelledby="<?php echo $headingId ?>">
                    <div class="panel-body">
                        <p>Signing time: <?php echo $signer->signingTime ?></p>

                        <p>Message
                            digest: <?php echo $signer->messageDigest->algorithm->getName() . " " . $signer->messageDigest->hexValue ?></p>
                        <?php if ($signer->signaturePolicy != null) { ?>
                            <p>Signature policy: <?php echo $signer->signaturePolicy->oid ?></p>
                        <?php } ?>
                        <p>
                            Signer information:
                        <ul>
                            <li>Subject: <?php echo $signer->certificate->subjectName->commonName ?></li>
                            <li>Email: <?php echo $signer->certificate->emailAddress ?></li>
                            <li>
                                ICP-Brasil fields
                                <ul>
                                    <li>Tipo de
                                        certificado: <?php echo $signer->certificate->pkiBrazil->certificateType ?></li>
                                    <li>CPF: <?php echo $signer->certificate->pkiBrazil->cpf ?></li>
                                    <li>Responsavel: <?php echo $signer->certificate->pkiBrazil->responsavel ?></li>
                                    <li>Empresa: <?php echo $signer->certificate->pkiBrazil->companyName ?></li>
                                    <li>CNPJ: <?php echo $signer->certificate->pkiBrazil->cnpj ?></li>
                                    <li>
                                        RG: <?php echo $signer->certificate->pkiBrazil->rgNumero . " " . $signer->certificate->pkiBrazil->rgEmissor . " " . $signer->certificate->pkiBrazil->rgEmissorUF ?></li>
                                    <li>
                                        OAB: <?php echo $signer->certificate->pkiBrazil->oabNumero . " " . $signer->certificate->pkiBrazil->oabUF ?></li>
                                </ul>
                            </li>
                        </ul>
                        </p>
                        <?php if ($signer->validationResults != null) { ?>
                            <p>Validation results:<br/>
                                <textarea style="width: 100%" rows="20"><?php echo $signer->validationResults ?></textarea>
                            </p>
                        <?php } ?>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>

    <h3>Actions:</h3>
    <ul>
        <li><a href="app-data/<?php echo $fileId ?>">Download signed file</a></li>
        <li><a href="printer-friendly-version.php?file=<?php echo $fileId ?>">Download a printer-friendly version</a></li>
    </ul>
</div>

</body>
</html>

<?php
/*
 * This file submits a PDF file to Rest PKI for inspection of its signatures and renders the results.
 */

require __DIR__ . '/vendor/autoload.php';

use Lacuna\RestPki\Legacy\PadesSignatureExplorer;
use Lacuna\RestPki\Legacy\StandardSignaturePolicies;

// Our demo only works if a userfile is given to work with.
$userfile = isset($_GET['userfile']) ? $_GET['userfile'] : null;
if (empty($userfile)) {
    throw new \Exception("No file was uploaded");
}

// Get an instance of the PadesSignatureExplorer class, used to open/validate PDF signatures.
$sigExplorer = new PadesSignatureExplorer(getRestPkiClient());

// Set the PDF file to be inspected.
$sigExplorer->setSignatureFile("app-data/{$userfile}");

// Specify that we want to validate the signatures in the file, not only inspect them.
$sigExplorer->setValidate(true);

// Accept any PAdES singature as long as is trusted by the security context.
$sigExplorer->setDefaultSignaturePolicyId(StandardSignaturePolicies::PADES_BASIC);

// Specify the security context to be used to determine trust in the certificate chain. We have encapsulated the
// security context choice on util.php.
$sigExplorer->setSecurityContextId(getSecurityContextId());

// Call the open() method, which returns the signature file's information.
$signature = $sigExplorer->open();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Open existing PAdES Signature</title>
    <meta charset="utf-8">
    <?php include 'includes.php' // jQuery and other libs (used only to provide a better user experience, but NOT required to use the Web PKI component). ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely. ?>

<div class="container">

    <h2>Open existing PAdES Signature</h2>

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
                           href="#<?php echo $collapseId ?>" aria-expanded="true"
                           aria-controls="<?php echo $collapseId ?>">
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
                        <?php if ($signer->signingTime != null) { ?>
                            <p>Signing time: <?= date('d/m/Y H:i', strtotime($signer->signingTime)) ?></p>
                        <?php } ?>

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
</div>

</body>
</html>

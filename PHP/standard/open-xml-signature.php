<?php
/*
 * This file submits a Xml file to Rest PKI for inspection and renders the results.
 */

require __DIR__ . '/vendor/autoload.php';

use Lacuna\RestPki\StandardSignaturePolicies;
use Lacuna\RestPki\StandardSecurityContexts;
use Lacuna\RestPki\XmlSignatureExplorer;

// This function is called below. It encapsulates examples of signature validation parameters.
function setValidationParameters(XmlSignatureExplorer $sigExplorer, $caseNumber)
{
    switch ($caseNumber) {
        /*
            Example #1: accept any valid XmlDSig signature as long as the signer has an ICP-Brasil certificate

            These parameters will only accept signatures made with ICP-Brasil certificates that comply with the
            minimal security features defined in the XmlDSig standard. The signatures need not, however, follow
            the extra requirements defined in the ICP-Brasil signature policy documentation (DOC-ICP-15.03).
         */
        case 1:
            // By omitting the accepted policies catalog and defining a default policy, we're telling Rest PKI to
            // validate all signatures in the file with the default policy -- even signatures with an explicit signature
            // policy.
            $sigExplorer->acceptableExplicitPolicies = null;
            $sigExplorer->defaultSignaturePolicy = StandardSignaturePolicies::XML_DSIG_BASIC;
            // The XmlDSigBasic policy requires us to choose a security context
            $sigExplorer->securityContext = StandardSecurityContexts::PKI_BRAZIL;
            break;

        /*
            Example #2: accept any valid XmlDSig signature as long as the signer is trusted by Windows

            Same case as example #1, but using the WindowsServer trust arbitrator
         */
        case 2:
            $sigExplorer->acceptableExplicitPolicies = null;
            $sigExplorer->defaultSignaturePolicy = StandardSignaturePolicies::XML_DSIG_BASIC;
            $sigExplorer->securityContext = StandardSecurityContexts::WINDOWS_SERVER;
            break;
    }
}

// Our demo only works if a userfile is given to work with
$userfile = isset($_GET['userfile']) ? $_GET['userfile'] : null;
if (empty($userfile)) {
    throw new \Exception("No file was uploaded");
}

// Get an instance of the XmlSignatureExplorer class, used to open/validate XML signatures
$sigExplorer = new XmlSignatureExplorer(getRestPkiClient());

// Set the XML file
$sigExplorer->setSignatureFileFromPath("app-data/{$userfile}");

// Specify that we want to validate the signatures in the file, not only inspect them
$sigExplorer->validate = true;

// Parameters for the signature validation. We have encapsulated this code in a method to include several
// possibilities depending on the argument passed. Experiment changing the argument to see different validation
// configurations. Once you decide which is best for your case, you can place the code directly here.
setValidationParameters($sigExplorer, 1);
// try changing this number ----------^ for different validation parameters

// Call the open() method, which returns the signature file's information
$signatures = $sigExplorer->open();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Open existing CAdES Signature</title>
    <?php include 'includes.php' // jQuery and other libs (used only to provide a better user experience, but NOT
    // required to use the Web PKI component) ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely ?>

<div class="container">

    <h2>Open/validate signatures on an existing XML file</h2>

    <h3>The given file contains <?= count($signatures) ?> signatures:</h3>

    <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">

        <?php for ($i = 0; $i < count($signatures); $i++) {

            $signature = $signatures[$i];
            $collapseId = "signer_" . $i . "_collapse";
            $headingId = "signer_" . $i . "_heading";

            ?>

            <div class="panel panel-default">
                <div class="panel-heading" role="tab" id="<?= $headingId ?>">
                    <h4 class="panel-title">
                        <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                           href="#<?= $collapseId ?>" aria-expanded="true" aria-controls="<?= $collapseId ?>">
                            <?= $signature->certificate->subjectName->commonName ?>
                            <?php if ($signature->validationResults != null) { ?>
                                <text>-</text>
                                <?php if ($signature->validationResults->isValid()) { ?>
                                    <span style="color: green; font-weight: bold;">valid</span>
                                <?php } else { ?>
                                    <span style="color: red; font-weight: bold;">invalid</span>
                                <?php } ?>
                            <?php } ?>
                        </a>
                    </h4>
                </div>
                <div id="<?= $collapseId ?>" class="panel-collapse collapse" role="tabpanel"
                     aria-labelledby="<?= $headingId ?>">
                    <div class="panel-body">
                        <p>Type: <?= $signature->type ?></p>
                        <?php if ($signature->signedElement != null) { ?>
                            <p>
                                Signed element: <?= $signature->signedElement->localName ?>
                                <?php if (empty($signature->signedElement->namespaceUri)) { ?>
                                    <text>(xmlns: <?= $signature->signedElement->namespaceUri ?>)</text>
                                <?php } ?>
                            </p>
                        <?php } ?>
                        <p>Signing time: <?= $signature->signingTime ?></p>
                        <?php if ($signature->signaturePolicy != null) { ?>
                            <p>Signature policy: <?= $signature->signaturePolicy->oid ?></p>
                        <?php } ?>
                        <p>
                            Signer information:
                        <ul>
                            <li>Subject: <?= $signature->certificate->subjectName->commonName ?></li>
                            <li>Email: <?= $signature->certificate->emailAddress ?></li>
                            <li>
                                ICP-Brasil fields
                                <ul>
                                    <li>Tipo de
                                        certificado: <?= $signature->certificate->pkiBrazil->certificateType ?></li>
                                    <li>CPF: <?= $signature->certificate->pkiBrazil->cpf ?></li>
                                    <li>Responsavel: <?= $signature->certificate->pkiBrazil->responsavel ?></li>
                                    <li>Empresa: <?= $signature->certificate->pkiBrazil->companyName ?></li>
                                    <li>CNPJ: <?= $signature->certificate->pkiBrazil->cnpj ?></li>
                                    <li>
                                        RG: <?= $signature->certificate->pkiBrazil->rgNumero . " " . $signature->certificate->pkiBrazil->rgEmissor . " " . $signature->certificate->pkiBrazil->rgEmissorUF ?></li>
                                    <li>
                                        OAB: <?= $signature->certificate->pkiBrazil->oabNumero . " " . $signature->certificate->pkiBrazil->oabUF ?></li>
                                </ul>
                            </li>
                        </ul>
                        </p>
                        <?php if ($signature->validationResults != null) { ?>
                            <p>Validation results:<br/>
                                <textarea style="width: 100%" rows="20"><?= $signature->validationResults ?></textarea>
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

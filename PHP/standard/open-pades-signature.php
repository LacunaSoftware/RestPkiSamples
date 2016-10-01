<?php
/*
 * This file submits a PDF file to Rest PKI for inspection of its signatures and renders the results.
 */

// The file RestPki.php contains the helper classes to call the REST PKI API
require_once 'RestPki.php';

// The file util.php contains the function getRestPkiClient(), which gives us an instance of the RestPkiClient class
// initialized with the API access token
require_once 'util.php';

use Lacuna\PadesSignatureExplorer;
use Lacuna\StandardSignaturePolicies;
use Lacuna\StandardSecurityContexts;
use Lacuna\StandardSignaturePolicyCatalog;

// This function is called below. It encapsulates examples of signature validation parameters.
function setValidationParameters($sigExplorer, $caseNumber)
{

    switch ($caseNumber) {

        /**
         * Example #1: accept any PAdES signature as long as the signer has an ICP-Brasil certificate (RECOMMENDED)
         *
         * These parameters will only accept signatures made with ICP-Brasil certificates that comply with the
         * minimal security features defined in the PAdES standard (ETSI TS 102 778). The signatures need not, however,
         * follow the extra requirements defined in the ICP-Brasil signature policy documentation (DOC-ICP-15.03).
         *
         * These are the recommended parameters for ICP-Brasil, since the official ICP-Brasil PAdES policies (released on 2016-06-01)
         * are still in adoption phase by most implementors.
         */
        case 1:
            // By omitting the accepted policies catalog and defining a default policy, we're telling Rest PKI to
            // validate all signatures in the file with the default policy -- even signatures with an explicit signature
            // policy.
            $sigExplorer->setDefaultSignaturePolicyId(StandardSignaturePolicies::PADES_BASIC_WITH_ICPBR_CERTS);
            break;

        /**
         * Example #2: accept any PAdES signature having a signature timestamp as long as the certificates for both the signer
		 * and the issuer of the timestamp are valid ICP-Brasil certificates
         *
         * These parameters will only accept signatures made with ICP-Brasil certificates that comply with the
         * minimal security features defined in the PAdES-T standard (ETSI TS 102 778). The signatures need not, however,
         * follow the extra requirements defined in the ICP-Brasil signature policy documentation (DOC-ICP-15.03).
         */
        case 2:
            // By omitting the accepted policies catalog and defining a default policy, we're telling Rest PKI to
            // validate all signatures in the file with the default policy -- even signatures with an explicit signature
            // policy.
            $sigExplorer->setDefaultSignaturePolicyId(StandardSignaturePolicies::PADES_T_WITH_ICPBR_CERTS);
            break;

		/**
         * Example #3: accept only 100%-compliant ICP-Brasil signatures
         */
        case 3:
            // By specifying a catalog of acceptable policies and omitting the default signature policy, we're telling
            // Rest PKI that only the policies in the catalog should be accepted
            $sigExplorer->setAcceptableExplicitPolicies(StandardSignaturePolicyCatalog::getPkiBrazilPades());
            break;

        /**
         * Example #4: accept any PAdES signature as long as the signer is trusted by Windows
         *
         * Same case as example #1, but using the WindowsServer trust arbitrator
         */
        case 4:
            $sigExplorer->setDefaultSignaturePolicyId(StandardSignaturePolicies::PADES_BASIC);
            $sigExplorer->setSecurityContextId(StandardSecurityContexts::WINDOWS_SERVER);
			// You can use any security context above, for instance a customized security context with your private PKI root certificate
            break;

        /**
         * Example #5: accept only 100%-compliant ICP-Brasil signatures that provide signer certificate protection.
         *
         * "Signer certificate protection" means that a signature keeps its validity even after the signer certificate
         * is revoked or expires. On ICP-Brasil, this translates to policies AD-RT and up (not AD-RB).
         */
        case 5:
            $sigExplorer->setAcceptableExplicitPolicies(
                StandardSignaturePolicyCatalog::getPkiBrazilPadesWithSignerCertificateProtection());
            break;

    }
}

// Our demo only works if a userfile is given to work with
$userfile = isset($_GET['userfile']) ? $_GET['userfile'] : null;
if (empty($userfile)) {
    throw new \Exception("No file was uploaded");
}

// Get an instance of the PadesSignatureExplorer class, used to open/validate PDF signatures
$sigExplorer = new PadesSignatureExplorer(getRestPkiClient());

// Set the PDF file to be inspected
$sigExplorer->setSignatureFile("app-data/{$userfile}");

// Specify that we want to validate the signatures in the file, not only inspect them
$sigExplorer->setValidate(true);

// Parameters for the signature validation. We have encapsulated this code in a method to include several
// possibilities depending on the argument passed. Experiment changing the argument to see different validation
// configurations. Once you decide which is best for your case, you can place the code directly here.
setValidationParameters($sigExplorer, 1);
// try changing this number ----------^ for different validation parameters

// Call the open() method, which returns the signature file's information
$signature = $sigExplorer->open();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Open existing PAdES Signature</title>
    <?php include 'includes.php' // jQuery and other libs (used only to provide a better user experience, but NOT
    // required to use the Web PKI component) ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely ?>

<div class="container">

    <h2>Open existing PAdES Signature</h2>

    <h3>The given file contains <?= count($signature->signers) ?> signatures:</h3>

    <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">

        <?php for ($i = 0; $i < count($signature->signers); $i++) {

            $signer = $signature->signers[$i];
            $collapseId = "signer_" . $i . "_collapse";
            $headingId = "signer_" . $i . "_heading";

            ?>

            <div class="panel panel-default">
                <div class="panel-heading" role="tab" id="<?= $headingId ?>">
                    <h4 class="panel-title">
                        <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                           href="#<?= $collapseId ?>" aria-expanded="true" aria-controls="<?= $collapseId ?>">
                            <?= $signer->certificate->subjectName->commonName ?>
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
                <div id="<?= $collapseId ?>" class="panel-collapse collapse" role="tabpanel"
                     aria-labelledby="<?= $headingId ?>">
                    <div class="panel-body">
                        <p>Signing time: <?= $signer->signingTime ?></p>

                        <p>Message
                            digest: <?= $signer->messageDigest->algorithm->getName() . " " . $signer->messageDigest->hexValue ?></p>
                        <?php if ($signer->signaturePolicy != null) { ?>
                            <p>Signature policy: <?= $signer->signaturePolicy->oid ?></p>
                        <?php } ?>
                        <p>
                            Signer information:
                        <ul>
                            <li>Subject: <?= $signer->certificate->subjectName->commonName ?></li>
                            <li>Email: <?= $signer->certificate->emailAddress ?></li>
                            <li>
                                ICP-Brasil fields
                                <ul>
                                    <li>Tipo de
                                        certificado: <?= $signer->certificate->pkiBrazil->certificateType ?></li>
                                    <li>CPF: <?= $signer->certificate->pkiBrazil->cpf ?></li>
                                    <li>Responsavel: <?= $signer->certificate->pkiBrazil->responsavel ?></li>
                                    <li>Empresa: <?= $signer->certificate->pkiBrazil->companyName ?></li>
                                    <li>CNPJ: <?= $signer->certificate->pkiBrazil->cnpj ?></li>
                                    <li>
                                        RG: <?= $signer->certificate->pkiBrazil->rgNumero . " " . $signer->certificate->pkiBrazil->rgEmissor . " " . $signer->certificate->pkiBrazil->rgEmissorUF ?></li>
                                    <li>
                                        OAB: <?= $signer->certificate->pkiBrazil->oabNumero . " " . $signer->certificate->pkiBrazil->oabUF ?></li>
                                </ul>
                            </li>
                        </ul>
                        </p>
                        <?php if ($signer->validationResults != null) { ?>
                            <p>Validation results:<br/>
                                <textarea style="width: 100%" rows="20"><?= $signer->validationResults ?></textarea>
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

<?php

use Lacuna\RestPki\PadesSignatureExplorer;
use Lacuna\RestPki\StandardSignaturePolicies;
use Lacuna\RestPki\StandardSecurityContexts;
use Lacuna\RestPki\PdfMark;
use Lacuna\RestPki\PdfMarkImage;
use Lacuna\RestPki\PdfMarkImageElement;
use Lacuna\RestPki\PdfTextSection;
use Lacuna\RestPki\PdfMarkTextElement;
use Lacuna\RestPki\PdfMarkQRCodeElement;
use Lacuna\RestPki\PdfMarker;
use Lacuna\RestPki\Color;

require __DIR__ . '/vendor/autoload.php';

function getVerificationCode($fileId)
{
    // Initialize or resume session
    if (session_status() != PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (isset($_SESSION['Files/' . $fileId . '/Code'])) {
        return $_SESSION['Files/' . $fileId . '/Code'];
    }
    return null;
}

function setVerificationCode($fileId, $code)
{
    // Initialize or resume session
    if (session_status() != PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION['Files/' . $fileId . '/Code'] = $code;
    $_SESSION['Codes/' . $code] = $fileId;
}

function lookupVerificationCode($code)
{

    if (empty($code)) {
        return null;
    }

    // Initialize or resume session
    if (session_status() != PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (isset($_SESSION['Codes/' . $code])) {
        return $_SESSION['Codes/' . $code];
    }
    return null;
}

function generatePrinterFriendlyVersion($pdfPath, $verificationCode)
{
    // Set the parameters used on the printer-friendly version
    $verificationSiteNameWithArticle = 'a Minha Central de Verificação';
    $verificationSite = 'http://localhost:8000';
    $verificationLinkFormat = 'http://localhost:8000/check.php?code=';
    $normalFontSize = 12;
    $timeZoneDisplayName = 'horário de Brasília';
    $breakline = PHP_EOL; // Using PHP's global variable PHP_EOL that returns a OS independent break-line

    $client = getRestPkiClient();
    $verificationLink = $verificationLinkFormat . $verificationCode;

    // 1. Upload the PDF
    $blob = $client->uploadFileFromPath($pdfPath);

    // 2. Inspect signatures on the uploaded PDF
    $signatureExplorer = new PadesSignatureExplorer($client);
    $signatureExplorer->validate = true;
    $signatureExplorer->defaultSignaturePolicy = StandardSignaturePolicies::PADES_BASIC;
    $signatureExplorer->securityContext = StandardSecurityContexts::PKI_BRAZIL;
    $signatureExplorer->setSignatureFileFromBlob($blob);
    $signature = $signatureExplorer->open();

    // 3. Create PDF wiht verification information from uploaded PDF

    $pdfMarker = new PdfMarker($client);
    $pdfMarker->setFileFromBlob($blob);

    // Build string with joined names of signers (see method _getDisplayName below)
    $certDisplayNames = [];
    foreach ($signature->signers as $signer) {
        array_push($certDisplayNames, _getDisplayName($signer->certificate));
    }
    $signerNames = joinStringsPt($certDisplayNames);
    $allPagesMessage = 'Este documento foi assinado digitalmente por ' . $signerNames
        . '.' . $breakline . 'Para verificar a validate das assinaturass acesse ' . $verificationSiteNameWithArticle
        . ' em ' . $verificationSite . ' e informe o código ' . $verificationCode;

    // ICP-Brasil logo on bottom-right corner of every page (except on the page which will be created at the end of the
    // document)
    $pdfMark = new PdfMark();
    $pdfMark->pageOption = 'AllPages';
    $pdfMark->container = [
        'width' => 1,
        'right' => 1,
        'height' => 1,
        'bottom' => 1
    ];
    $element = new PdfMarkImageElement();
    $element->opacity = 75;
    $element->image = new PdfMarkImage(getIcpBrasilLogoContent(), 'image/png');
    array_push($pdfMark->elements, $element);
    array_push($pdfMarker->marks, $pdfMark);

    // Summary on bottom margin of every page (except on the page which will be created at the end of the document)
    $pdfMark = new PdfMark();
    $pdfMark->pageOption = 'AllPages';
    $pdfMark->container = [
        'height' => 2,
        'bottom' => 0,
        'left' => 1.5,
        'right' => 3.5
    ];
    $element = new PdfMarkTextElement();
    $element->opacity = 75;
    array_push($element->textSections, new PdfTextSection($allPagesMessage));
    array_push($pdfMark->elements, $element);
    array_push($pdfMarker->marks, $pdfMark);

    // Summary on right margin of every page (except on the page which wil be created at the end of the document),
    // rotated 90 degrees counterclockwise (text goes up)
    $pdfMark = new PdfMark();
    $pdfMark->pageOption = 'AllPages';
    $pdfMark->container = [
        'width' => 2,
        'right' => 0,
        'top' => 1.5,
        'bottom' => 3.5
    ];
    $element = new PdfMarkTextElement();
    $element->rotation = 270; // 90 degrees counterclockwise;
    $element->opacity = 75;
    array_push($element->textSections, new PdfTextSection($allPagesMessage));
    array_push($pdfMark->elements, $element);
    array_push($pdfMarker->marks, $pdfMark);

    // Create a "manifest" mark on a new page added on the end of the document. We'll add several elements to this mark.
    $manifestMark = new PdfMark();
    $manifestMark->pageOption = 'NewPage';
    // This mark's container is the whole page with 1-inch margins
    $manifestMark->container = [
        'top' => 2.54,
        'bottom' => 2.54,
        'left' => 2.54,
        'right' => 2.54
    ];

    // We'll keep track of our "vertical offset" as we add elements to the mark
    $verticalOffset = 0;
    $elementHeight = null;

    $elementHeight = 3;
    // ICP-Brasil logo on the upper-left corner
    $element = new PdfMarkImageElement();
    $element->relativeContainer = [
        'height' => $elementHeight,
        'top' => $verticalOffset,
        'width' => $elementHeight, // using elementHeight as width because the image has square shape
        'left' => 0
    ];
    $element->image = new PdfMarkImage(getIcpBrasilLogoContent(), "image/png");
    array_push($manifestMark->elements, $element);

    // QR Code with the verification link on the upper-right corner
    $element = new PdfMarkQRCodeElement();
    $element->relativeContainer = [
        'height' => $elementHeight,
        'top' => $verticalOffset,
        'width' => $elementHeight, // using elementHeight as width because the image has square shape
        'right' => 0
    ];
    $element->qrCodeData = $verificationLink;
    array_push($manifestMark->elements, $element);

    // Header "VERIFICAÇÃO DAS ASSINATURAS" centered between ICP-Brasil logo and QR Code
    $element = new PdfMarkTextElement();
    $element->relativeContainer = [
        'height' => $elementHeight,
        'top' => $verticalOffset + 0.2,
        // Full width
        'left' => 0,
        'right' => 0
    ];
    $element->align = 'Center';
    $textSection = new PdfTextSection();
    $textSection->fontSize = $normalFontSize * 1.6;
    $textSection->text = 'VERIFICAÇÃO DAS' . $breakline . 'ASSINATURAS'; // Using PHP global break-line variable PHP_EOL
    array_push($element->textSections, $textSection);
    array_push($manifestMark->elements, $element);
    $verticalOffset += $elementHeight;

    // Vertical padding
    $verticalOffset += 1.7;

    // Header with verification code
    $elementHeight = 2;
    $element = new PdfMarkTextElement();
    $element->relativeContainer = [
        'height' => $elementHeight,
        'top' => $verticalOffset,
        // Full width
        'left' => 0,
        'right' => 0
    ];
    $element->align = 'Center';
    $textSection = new PdfTextSection();
    $textSection->fontSize = $normalFontSize * 1.2;
    $textSection->text = 'Código para verificação: ' . $verificationCode;
    array_push($element->textSections, $textSection);
    array_push($manifestMark->elements, $element);
    $verticalOffset += $elementHeight;

    // Paragraph saying "this document was signed by the following signers etc" and mentioning the time zone of the
    // date/times below
    $elementHeight = 2.5;
    $element = new PdfMarkTextElement();
    $element->relativeContainer = [
        'height' => $elementHeight,
        'top' => $verticalOffset,
        // Full width
        'left' => 0,
        'right' => 0
    ];
    $textSection = new PdfTextSection();
    $textSection->fontSize = $normalFontSize;
    $textSection->text = 'Este documento foi assinado digitalmente pelos seguintes signatários nas datas indicadas ('
        . $timeZoneDisplayName . ')';
    array_push($element->textSections, $textSection);
    array_push($manifestMark->elements, $element);
    $verticalOffset += $elementHeight;

    // Iterate signers
    foreach ($signature->signers as $signer) {

        $elementHeight = 1.5;

        // Green "check" or red "X" icon depending on result of validation for this signer
        $element = new PdfMarkImageElement();
        $element->relativeContainer = [
            'height' => 0.5,
            'top' => $verticalOffset + 0.2,
            'width' => 0.5,
            'left' => 0
        ];
        $element->image = new PdfMarkImage(getValidationResultIcon($signer->validationResults->isValid()), 'image/png');
        array_push($manifestMark->elements, $element);
        // Description of signer (see method _getSignerDescription() below
        $element = new PdfMarkTextElement();
        $element->relativeContainer = [
            'height' => $elementHeight,
            'top' => $verticalOffset,
            'left' => 0.8,
            'right' => 0
        ];
        $textSection = new PdfTextSection();
        $textSection->fontSize = $normalFontSize;
        $textSection->text = _getSignerDescription($signer);
        array_push($element->textSections, $textSection);
        array_push($manifestMark->elements, $element);

        $verticalOffset += $elementHeight;
    }

    // Some vertical padding from last signer
    $verticalOffset += 1;

    // Paragraph with link to verification site and citing both the verification code above and the verification link
    // below
    $elementHeight = 2.5;
    $element = new PdfMarkTextElement();
    $element->relativeContainer = [
        'height' => $elementHeight,
        'top' => $verticalOffset,
        // Full width
        'left' => 0,
        'right' => 0
    ];
    $textSection = new PdfTextSection();
    $textSection->fontSize = $normalFontSize;
    $textSection->text = 'Para verificar a validade das assinaturas, acesse ' . $verificationSiteNameWithArticle
        . ' em ';
    array_push($element->textSections, $textSection);
    $textSection = new PdfTextSection();
    $textSection->fontSize = $normalFontSize;
    $textSection->color = new Color("#0000FF", 100);
    $textSection->text = $verificationSite;
    array_push($element->textSections, $textSection);
    $textSection = new PdfTextSection();
    $textSection->fontSize = $normalFontSize;
    $textSection->text = ' e informe o código acima ou acesse o link abaixo:';
    array_push($element->textSections, $textSection);
    array_push($manifestMark->elements, $element);
    $verticalOffset += $elementHeight;

    // Verification link
    $element = new PdfMarkTextElement();
    $element->relativeContainer = [
        'height' => $elementHeight,
        'top' => $verticalOffset,
        // Full width
        'left' => 0,
        'right' => 0
    ];
    $element->align = 'Center';
    $textSection = new PdfTextSection();
    $textSection->fontSize = $normalFontSize;
    $textSection->color = new Color("#0000FF", 100);
    $textSection->text = $verificationLink;
    array_push($element->textSections, $textSection);
    array_push($manifestMark->elements, $element);

    // Apply marks
    array_push($pdfMarker->marks, $manifestMark);
    $result = $pdfMarker->apply();

    // Return result
    return $result->getContentRaw();
}

function _getDisplayName($cert)
{
    if (isset($cert->pkiBrazil->responsavel)) {
        return $cert->pkiBrazil->responsavel;
    }
    return $cert->pkiBrazil->commonName;
}

function _getDescription($cert)
{
    $text = '';
    $text .= _getDisplayName($cert);
    if (isset($cert->pkiBrazil->cpf)) {
        $text .= ' (CPF ' . $cert->pkiBrazil->cpf . ')';
    }
    if (isset($cert->pkiBrazil->cnpj)) {
        $text .= ', empresa ' . $cert->pkiBrazil->companyName . ' (CNPJ ' . $cert->pkiBrazil->cnpj . ')';
    }
    return $text;
}

function _getSignerDescription($signer)
{
    $text = '';
    $text .= _getDescription($signer->certificate);
    if (isset($signer->signingTime)) {
        $text .= ' em ' . $signer->signingTime;
    }
    return $text;
}

const express = require('express');
const moment = require('moment');
const {
   PadesSignatureExplorer,
   PdfMarker,
   StandardSignaturePolicies,
   PdfMark,
   PdfMarkPageOptions,
   PdfMarkImageElement,
   PdfMarkImage,
   PdfMarkTextElement,
   PdfTextSection,
   PdfMarkQRCodeElement,
   Color
} = require('restpki-client');

const { StorageMock } = require('../storage-mock');
const { Util } = require('../util');

let router = express.Router();
let appRoot = process.cwd();

// #############################################################################
// Configuration of the Printer-Friendly version
// #############################################################################

// Name of your website, with preceding article (article in lowercase).
const verificationSiteNameWithArticle = 'a Minha Central de Verificação';

// Publicly accessible URL of your website. Preferable HTTPS.
const verificationSite = 'http://localhost:3000';

// Format of the verification link, without the verification code, that is added
// on generatePrinterFriendlyVersion() method.
const verificationLinkFormat = 'http://localhost:3000/check?code=';

// "Normal" font size. Sizes of header fonts are defined based on this size.
const normalFontSize = 12;

// Date format when converting date into a string (see
const dateFormat = 'DD/MM/YYYY HH:mm';

// Display name of the time zone chosen above
const timeZoneDisplayName = 'horário de Brasília';

// You may also change texts, positions and more by editing directly the method
// generatePrinterFriendlyVersion() below.
// #############################################################################

/*
 * GET /printer-friendly-version
 *
 * This generates a printer-friendly version from a signature file using REST
 * PKI.
 */
router.get('/', function(req, res, next) {

   // Our demo only works if a "file" is given to work with.
   let fileId = req.query.file;
   if (!fileId) {
      res.status(404).send('Not found');
      return;
   }

   // Locate document and read content.
   let filePath = appRoot + '/public/app-data/' + fileId;

   // Check if doc already has a verification code registered on storage.
   let verificationCode = StorageMock.getVerificationCode(req.session, fileId);
   if (!verificationCode) {
      // If not, generate a code and register it.
      verificationCode = Util.generateVerificationCode();
      StorageMock.setVerificationCode(req.session, fileId, verificationCode);
   }

   // Generate the printer-friendly version.
   generatePrinterFriendlyVersion(filePath, verificationCode, res.locals.environment)
   .then((pfvContent) => {

      // Return the generate file.
      res.type('pdf');
      res.send(pfvContent);

   })
   .catch((err) => next(err));

});

function generatePrinterFriendlyVersion(pdfPath, verificationCode, environment) {

   // The verification code is generated without hyphens to save storage space
   // and avoid copy-and-paste problems. On the PDF generation, we use the
   // "formatted" version, with hyphens (which will later be discarded on the
   // verification page).
   let formattedVerificationCode = Util.formatVerificationCode(verificationCode);

   // Build the verification link from teh constant verificationLinkFormat (see
   // above) and the formatted verification code.
   let verificationLink = verificationLinkFormat + formattedVerificationCode;

   // 1. Inspect signature on the uploaded PDF.

   // Get an instance of the PadesSignatureExplorer class, used to open/validate
   // PDF signatures.
   let signatureExplorer = new PadesSignatureExplorer(Util.getRestPkiClient());

   // Set the PDF file to be inspected.
   signatureExplorer.setSignatureFileFromPath(pdfPath);

   // Specify that we want to validate the signatures in the file, not only
   // inspect them.
   signatureExplorer.validate = true;

   // Specify the signature for signature validation. On this sample, we will
   // accept any valid PAdES signature as long as the signer is trusted by the
   // security context.
   signatureExplorer.defaultSignaturePolicyId = StandardSignaturePolicies.PADES_BASIC;

   // Specify the security context to be used to determine trust in the
   // certificate chain. We have encapsulated the security context choice on
   // util.js.
   signatureExplorer.securityContextId = Util.getSecurityContextId(environment);

   // Call the open() method, which returns the signature file's information.
   return new Promise((resolve, reject) => {

      signatureExplorer.open()
      .then((signature) => {

         // 2. Create PDF with verification info from uploaded PDF.

         let pdfMarker = new PdfMarker(Util.getRestPkiClient());
         pdfMarker.setFileFromPath(pdfPath);

         // Build string with joined names of signers (see method _getDisplayName()
         // below).
         let certDisplayNames = [];
         signature.signers.forEach(function(signer) {
            certDisplayNames.push(_getDisplayName(signer.certificate));
         });
         let signerNames = Util.joinStringPt(certDisplayNames);
         let allPagesMessage = `Este documento foi assinado digitalmente por ${signerNames}.\n` +
             `Para verificar a validade das assinaturas acesse ${verificationSiteNameWithArticle}` +
             ` em ${verificationSite} e informe o código ${formattedVerificationCode}`;

         let pdfMark;
         let manifestMark;
         let element;
         let textSection;

         pdfMark = new PdfMark();
         pdfMark.pageOption = PdfMarkPageOptions.ALL_PAGES;
         pdfMark.container = {
            width: 1,
            right: 1,
            height: 1,
            bottom: 1
         };
         element = new PdfMarkImageElement();
         element.opacity = 75;
         element.image = new PdfMarkImage(Util.getIcpBrasilLogoContent(), 'image/png');
         pdfMark.elements.push(element);
         pdfMarker.marks.push(pdfMark);

         // Summary on bottom margin of every page (except on the page which will
         // be created at the end of the document).
         pdfMark = new PdfMark();
         pdfMark.pageOption = PdfMarkPageOptions.ALL_PAGES;
         pdfMark.container = {
            left: 1.5,
            right: 3.5,
            height: 2,
            bottom: 0
         };
         element = new PdfMarkTextElement();
         element.opacity = 75;
         element.textSections.push(new PdfTextSection(allPagesMessage));
         pdfMark.elements.push(element);
         pdfMarker.marks.push(pdfMark);

         // Summary on right margin of every page (except on the page which will be
         // created at the end of the document), rotated 90 degrees
         // counter-clockwise (text goes up).
         pdfMark = new PdfMark();
         pdfMark.pageOption = PdfMarkPageOptions.ALL_PAGES;
         pdfMark.container = {
            width: 2,
            right: 0,
            top: 1.5,
            bottom: 3.5
         };
         element = new PdfMarkTextElement();
         element.rotation = 90;
         element.opacity = 75;
         element.textSections.push(new PdfTextSection(allPagesMessage));
         pdfMark.elements.push(element);
         pdfMarker.marks.push(pdfMark);

         // Create a "manifest" mark on a new page added on the end of the
         // document. We'll add several elements to this mark.
         manifestMark = new PdfMark();
         manifestMark.pageOption = PdfMarkPageOptions.NEW_PAGE;
         manifestMark.container = {
            top: 1.5,
            bottom: 1.5,
            left: 1.5,
            right: 1.5
         };

         // We'll keep track of our "vertical offset" as we add elements to the
         // mark.
         let verticalOffset = 0;
         let elementHeight;

         elementHeight = 3;
         // ICP-Brasil logo on the upper-left corner.
         element = new PdfMarkImageElement();
         element.relativeContainer = {
            height: elementHeight,
            top: verticalOffset,
            width: elementHeight, // Using elementHeight as width because the image has square format.
            left: 0
         };
         element.image = new PdfMarkImage(Util.getIcpBrasilLogoContent(), 'image/png');
         manifestMark.elements.push(element);

         // QR Code with the verification link on the upper-right corner.
         element = new PdfMarkQRCodeElement();
         element.relativeContainer = {
            height: elementHeight,
            top: verticalOffset,
            width: elementHeight, // Using elementHeight as width because the image has square format.
            right: 0
         };
         element.qrCodeData = verificationLink;
         manifestMark.elements.push(element);

         // Header "VERIFICAÇÃO DAS ASSINATURAS" centered between ICP-Brasil logo
         // and QR Code.
         element = new PdfMarkTextElement();
         element.relativeContainer = {
            height: elementHeight,
            top: verticalOffset + 0.2,
            // Full width.
            left: 0,
            right: 0
         };
         element.align = 'Center';
         textSection = new PdfTextSection();
         textSection.fontSize = normalFontSize * 1.6;
         textSection.text = 'VERIFICAÇÃO DAS\nASSINATURAS';
         element.textSections.push(textSection);
         manifestMark.elements.push(element);
         verticalOffset += elementHeight;

         // Vertical padding.
         verticalOffset += 1.7;

         // Header with verification code.
         elementHeight = 2;
         element = new PdfMarkTextElement();
         element.relativeContainer = {
            height: elementHeight,
            top: verticalOffset,
            // Full width.
            left: 0,
            right: 0
         };
         element.align = 'Center';
         textSection = new PdfTextSection();
         textSection.fontSize = normalFontSize * 1.2;
         textSection.text = `Código para verificação: ${formattedVerificationCode}`;
         element.textSections.push(textSection);
         manifestMark.elements.push(element);
         verticalOffset += elementHeight;

         // Paragraph saying "this document was signed by the following signers
         // etc" and mentioning the time zone of the date/times below.
         elementHeight = 2.5;
         element = new PdfMarkTextElement();
         element.relativeContainer = {
            height: elementHeight,
            top: verticalOffset,
            // Full width.
            left: 0,
            right: 0
         };
         textSection = new PdfTextSection();
         textSection.fontSize = normalFontSize;
         textSection.text = `Este document fo assinado digitalmente pelos seguintes signatários nas datas indicadas (${timeZoneDisplayName})`;
         element.textSections.push(textSection);
         manifestMark.elements.push(element);
         verticalOffset += elementHeight;

         // Iterate signers.
         for (let signer of signature.signers) {

            elementHeight = 1.5;

            // Green "check" or red "X" icon depending on result of validation for
            // this signer.
            element = new PdfMarkImageElement();
            element.relativeContainer = {
               height: 0.5,
               top: verticalOffset + 0.2,
               width: 0.5,
               left: 0
            };
            element.image = new PdfMarkImage(Util.getValidationResultIcon(signer.validationResults.isValid()), 'image/png');
            manifestMark.elements.push(element);

            // Description of signer (see method __getSignerDescription() below.
            element = new PdfMarkTextElement();
            element.relativeContainer = {
               height: elementHeight,
               top: verticalOffset,
               left: 0.8,
               right: 0
            };
            textSection = new PdfTextSection();
            textSection.fontSize = normalFontSize;
            textSection.text = _getSignerDescription(signer);
            element.textSections.push(textSection);
            manifestMark.elements.push(element);

            verticalOffset += elementHeight;
         }

         verticalOffset += 1.0;

         // Paragraph with link to verification site and citing both the
         // verification code above and the verification link below.
         elementHeight = 2.5;
         element = new PdfMarkTextElement();
         element.relativeContainer = {
            height: elementHeight,
            top: verticalOffset,
            // Full width
            left: 0,
            right: 0
         };
         textSection = new PdfTextSection();
         textSection.fontSize = normalFontSize;
         textSection.text = `Para verificar a validade das assinaturas, acesse ${verificationSiteNameWithArticle} em `;
         element.textSections.push(textSection);
         textSection = new PdfTextSection();
         textSection.fontSize = normalFontSize;
         textSection.color = Color.fromRGBString('#0000FF', 100);
         textSection.text = verificationSite;
         element.textSections.push(textSection);
         textSection = new PdfTextSection();
         textSection.fontSize = normalFontSize;
         textSection.text = ' e informe o código acima ou acesse o link abaixo:';
         element.textSections.push(textSection);
         manifestMark.elements.push(element);
         verticalOffset += elementHeight;

         // Verification link.
         elementHeight = 1.5;
         element = new PdfMarkTextElement();
         element.relativeContainer = {
            height: elementHeight,
            top: verticalOffset,
            // Full width
            left: 0,
            right: 0
         };
         element.align = 'Center';
         textSection = new PdfTextSection();
         textSection.fontSize = normalFontSize;
         textSection.color = Color.fromRGBString('#0000FF', 100);
         textSection.text = verificationLink;
         element.textSections.push(textSection);
         manifestMark.elements.push(element);

         // Apply marks.
         pdfMarker.marks.push(manifestMark);
         return pdfMarker.apply();

      })
      .then((result) => {
         resolve(new Buffer(result['content'], 'base64'));
      })
      .catch((err) => reject(err));

   });

}

function _getDisplayName(cert) {
   if (cert.pkiBrazil.responsavel) {
      return cert.pkiBrazil.responsavel;
   }
   return cert.pkiBrazil.commonName;
}

function _getDescription(cert) {
   let text = '';
   text += _getDisplayName(cert);
   if (cert.pkiBrazil.cpf) {
      text += ' (CPF ' + cert.pkiBrazil.cpfFormatted + ')';
   }
   if (cert.pkiBrazil.cnpj) {
      text += ', empresa ' + cert.pkiBrazil.companyName + ' (CNPJ ' +
          cert.pkiBrazil.cnpjFormatted + ')';
   }
   return text;
}

function _getSignerDescription(signer) {
   let text = '';
   text += _getDescription(signer.certificate);
   if (signer.signingTime) {
      text += ' em ' + moment(signer.signingTime).format(dateFormat);
   }
   return text;
}

module.exports = router;
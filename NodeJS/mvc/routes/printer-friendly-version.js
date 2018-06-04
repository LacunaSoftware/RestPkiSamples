var express = require('express');
var request = require('request');
var fs = require('fs');
var moment = require('moment');

var restPki = require('../lacuna-restpki');
var storageMock = require('../storage-mock');
var util = require('../util');

var router = express.Router();
var appRoot = process.cwd();

// #############################################################################
// Configuration of the Printer-Friendly version
// #############################################################################

// Name of your website, with preceding article (article in lowercase).
var verificationSiteNameWithArticle = 'a Minha Central de Verificação';

// Publicly accessible URL of your website. Preferable HTTPS.
var verificationSite = 'http://localhost:3000';

// Format of the verification link, without the verification code, that is added
// on generatePrinterFriendlyVersion() method.
var verificationLinkFormat = 'http://localhost:3000/check?code=';

// "Normal" font size. Sizes of header fonts are defined based on this size.
var normalFontSize = 12;

// Date format when converting date into a string (see
var dateFormat = 'DD/MM/YYYY HH:mm';

// Display name of the time zone chosen above
var timeZoneDisplayName = 'horário de Brasília';

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

   // Our demo only works if a userfile is given to work with.
   var fileContent = null;
   if (req.query.file) {
      fileContent = fs.readFileSync(appRoot + '/public/app-data/' + req.query.file);
   } else {
      res.status(404).send('Not found');
      return;
   }

   // Check if doc already has a verification code registered on storage.
   var verificationCode = storageMock.getVerificationCode(req.session, req.query.file);
   if (!verificationCode) {
      // If not, generate a code and register it.
      verificationCode = util.generateVerificationCode();
      storageMock.setVerificationCode(req.session, req.query.file, verificationCode);
   }

   generatePrinterFriendlyVersion(fileContent, verificationCode, next)
   .then(function(pfvContent) {
      res.type('pdf');
      res.send(new Buffer(pfvContent, 'base64'));
   });

});

function generatePrinterFriendlyVersion(pdfContent, verificationCode, next) {

   // The verification code is generated without hyphens to save storage space
   // and avoid copy-and-paste problems. On the PDF generation, we use the
   // "formatted" version, with hyphens (which will later be discarded on the
   // verification page).
   var formattedVerificationCode = util.formatVerificationCode(verificationCode);

   // Request to be sent to REST PKI.
   var openRequest = {

      // Specify that we want to validate the signatures in the file, not only
      // inspect them.
      validate: true,

      // Specify the signature policy for signature validation. On this sample,
      // we will accept any PAdES signature as long as the signer has an
      // ICP-Brasil certificate.
      defaultSignaturePolicyId: restPki.standardSignaturePolicies.padesBasicWithPkiBrazilCerts,

      // Specify the security context.
      securityContextId: restPki.standardSecurityContexts.pkiBrazil,

      // Specify file to be inspected.
      file: {

         // Inform the file content encoded in Base64.
         content: new Buffer(pdfContent).toString('base64')
      }
   };

   return new Promise(function(resolve) {

      // 1. Inspect signatures on the uploaded PDF.

      // Call the action POST Api/PadesSignatures/Open, which open/validate the
      // signature.
      request.post(util.endpoint + 'Api/PadesSignatures/Open', {
         json: true,
         headers: {'Authorization': 'Bearer ' + util.accessToken},
         body: openRequest
      }, function(err, restRes, body) {

         if (restPki.checkResponse(err, restRes, body, next)) {

            // Parse output fields
            var signature = restRes.body;
            signature.signers.forEach(function(signer) {
               signer.validationResults = new restPki.ValidationResults(signer.validationResults);
               if (signer.signingTime) {
                  // Format date using moment package.
                  signer.signingTime = moment(signer.signingTime)
               }
               if (signer.certificate && signer.certificate.pkiBrazil) {
                  signer.certificate.pkiBrazil.cpfFormatted = util.formatCpf(signer.certificate.pkiBrazil.cpf);
                  signer.certificate.pkiBrazil.cnpjFormatted = util.formatCnpj(signer.certificate.pkiBrazil.cnpj);
               }
            });

            // 2. Get marks from function below. We'll use this marks to create PDF
            // with verification information from uploaded PDF.
            var marks = getMarks(signature, formattedVerificationCode);

            var applyRequest = {
               marks: marks,
               measurementUnits: 'Centimeters',
               file: {
                  content: new Buffer(pdfContent).toString('base64')
               }
            };

            // Call the action POST Api/Pdf/AddMarks, which apply the
            // marks on a PDF file.
            request.post(util.endpoint + 'Api/Pdf/AddMarks', {
               json: true,
               headers: {'Authorization': 'Bearer ' + util.accessToken},
               body: applyRequest
            }, function(err, restRes, body) {

               if (restPki.checkResponse(err, restRes, body, next)) {
                  resolve(restRes.body.file.content);
               }
            });
         }
      });

   });

}

function getMarks(signature, formattedVerificationCode) {

   var verificationLink = verificationLinkFormat + formattedVerificationCode;
   var marks = [];
   var pdfMark;
   var manifestMark;
   var element;
   var section;

   // Build string with joined names of signers (see method getDisplayName()
   // below).
   var certDisplayNames = [];
   signature.signers.forEach(function(signer) {
      certDisplayNames.push(_getDisplayName(signer.certificate));
   });
   var signerNames = util.joinStringPt(certDisplayNames);
   var allPagesMessage = 'Este documento foi assinado digitalmente por ' + signerNames +
       '.\n' + 'Para verificar a validade das assinaturas acesse ' + verificationSiteNameWithArticle +
       ' em ' + verificationSite + ' e informe o código ' + formattedVerificationCode;

   // ICP-Brasil logo on bottom-right corner of every page (except on the page
   // which will be created at the end of the document).
   pdfMark = {
      pageOption: 'AllPages',
      container: {
         width: 1,
         right: 1,
         height: 1,
         bottom: 1
      },
      elements: []
   };
   element = {
      elementType: 'Image',
      opacity: 75,
      image: {
         resource: {
            content: new Buffer(util.getIcpBrasilLogoContent()).toString('base64'),
            mimeType: 'image/png'
         }
      }
   };
   pdfMark.elements.push(element);
   marks.push(pdfMark);

   // Summary on bottom margin of every page (except on the page which will be
   // created at the end of the document).
   pdfMark = {
      pageOption: 'AllPages',
      container: {
         left: 1.5,
         right: 3.5,
         height: 2,
         bottom: 0
      },
      elements: []
   };
   element = {
      elementType: 'Text',
      opacity: 75,
      textSections: []
   };
   section = {
      style: 'Normal',
      text: allPagesMessage,
      color: {
         blue: 0,
         green: 0,
         red: 0,
         alpha: 100
      }
   };
   element.textSections.push(section);
   pdfMark.elements.push(element);
   marks.push(pdfMark);

   // Summary on right margin of every page (except on the page which will be
   // created at the end of the document), rotated 90 degrees counter-clockwise
   // (text goes up).
   pdfMark = {
      pageOption: 'AllPages',
      container: {
         width: 2,
         right: 0,
         top: 1.5,
         bottom: 3.5
      },
      elements: []
   };
   element = {
      elementType: 'Text',
      rotation: 90,
      opacity: 75,
      textSections: []
   };
   section = {
      style: 'Normal',
      text: allPagesMessage,
      color: {
         blue: 0,
         green: 0,
         red: 0,
         alpha: 100
      }
   };
   element.textSections.push(section);
   pdfMark.elements.push(element);
   marks.push(pdfMark);

   // Create a "manifest" mark on a new page added on the end of the document.
   // We'll add several elements to this mark.
   manifestMark = {
      pageOption: 'NewPage',
      container: {
         top: 1.5,
         bottom: 1.5,
         left: 1.5,
         right: 1.5
      },
      elements: []
   };

   // We'll keep track of our "vertical offset" as we add elements to the mark.
   var verticalOffset = 0;
   var elementHeight;

   elementHeight = 3;
   // ICP-Brasil logo on the upper-left corner.
   element = {
      elementType: 'Image',
      relativeContainer: {
         height: elementHeight,
         top: verticalOffset,
         width: elementHeight, // Using elementHeight as width because the image has square format.
         left: 0
      },
      image: {
         resource: {
            content: new Buffer(fs.readFileSync(appRoot + '/public/icp-brasil.png')).toString('base64'),
            mimeType: 'image/png'
         }
      }
   };
   manifestMark.elements.push(element);

   // QR Code with the verification link on the upper-right corner.
   element = {
      elementType: 'QRCode',
      relativeContainer: {
         height: elementHeight,
         top: verticalOffset,
         width: elementHeight, // Using elementHeight as width because the image has square format.
         right: 0
      },
      qrCodeData: verificationLink
   };
   manifestMark.elements.push(element);

   // Header "VERIFICAÇÃO DAS ASSINATURAS" centered between ICP-Brasil logo and
   // QR Code.
   element = {
      elementType: 'Text',
      relativeContainer: {
         height: elementHeight,
         top: verticalOffset + 0.2,
         // Full width.
         left: 0,
         right: 0
      },
      align: 'Center',
      textSections: []
   };
   section = {
      fontSize: normalFontSize * 1.6,
      text: 'VERIFICAÇÃO DAS\nASSINATURAS',
      style: 'Normal',
      color: {
         blue: 0,
         green: 0,
         red: 0,
         alpha: 100
      }
   };
   element.textSections.push(section);
   manifestMark.elements.push(element);
   verticalOffset += elementHeight;

   // Vertical padding.
   verticalOffset += 1.7;

   // Header with verification code.
   elementHeight = 2;
   element = {
      elementType: 'Text',
      relativeContainer: {
         height: elementHeight,
         top: verticalOffset,
         // Full width.
         left: 0,
         right: 0
      },
      align: 'Center',
      textSections: []
   };
   section = {
      fontSize: normalFontSize * 1.2,
      text: 'Código de verificação: ' + formattedVerificationCode,
      style: 'Normal',
      color: {
         blue: 0,
         green: 0,
         red: 0,
         alpha: 100
      }
   };
   element.textSections.push(section);
   manifestMark.elements.push(element);
   verticalOffset += elementHeight;

   // Paragraph saying "this document was signed by the following signers etc"
   // and mentioning the time zone of the date/times below.
   elementHeight = 2.5;
   element = {
      elementType: 'Text',
      relativeContainer: {
         height: elementHeight,
         top: verticalOffset,
         // Full width.
         left: 0,
         right: 0
      },
      textSections: []
   };
   section = {
      fontSize: normalFontSize,
      text: 'Este documento foi assinado digitalmente pelos seguintes signatários nas datas indicadas (' + timeZoneDisplayName + ')',
      style: 'Normal',
      color: {
         blue: 0,
         green: 0,
         red: 0,
         alpha: 100
      }
   };
   element.textSections.push(section);
   manifestMark.elements.push(element);
   verticalOffset += elementHeight;

   // Iterate signers.
   for (var i = 0; i < signature.signers.length; i++) {

      var signer = signature.signers[i];

      elementHeight = 1.5;

      // Green "check" or red "X" icon depending on result of validation for
      // this signer.
      element = {
         elementType: 'Image',
         relativeContainer: {
            height: 0.5,
            top: verticalOffset + 0.2,
            width: 0.5,
            left: 0
         },
         image: {
            resource: {
               content: new Buffer(util.getValidationResultIcon(signer.validationResults.isValid())).toString('base64'),
               mimeType: 'image/png'
            }
         }
      };
      manifestMark.elements.push(element);

      // Description of signer (see method _getSignerDescription() below).
      element = {
         elementType: 'Text',
         relativeContainer: {
            height: elementHeight,
            top: verticalOffset,
            left: 0.8,
            right: 0
         },
         textSections: []
      };
      section = {
         fontSize: normalFontSize,
         text: _getSignerDescription(signer),
         style: 'Normal',
         color: {
            blue: 0,
            green: 0,
            red: 0,
            alpha: 100
         }
      };
      element.textSections.push(section);
      manifestMark.elements.push(element);

      verticalOffset += 1.0;
   }

   verticalOffset += 0.5;

   // Paragraph with link to verification site and citing both the verification
   // code above and the verification link below.
   elementHeight = 2.5;
   element = {
      elementType: 'Text',
      relativeContainer: {
         height: elementHeight,
         top: verticalOffset,
         // Full width
         left: 0,
         right: 0
      },
      textSections: []
   };
   section = {
      fontSize: normalFontSize,
      text: 'Para verificar a validade das assinaturas, acesse ' + verificationSiteNameWithArticle + ' em ',
      style: 'Normal',
      color: {
         blue: 0,
         green: 0,
         red: 0,
         alpha: 100
      }
   };
   element.textSections.push(section);
   section = {
      fontSize: normalFontSize,
      color: {
         alpha: 100,
         blue: 255,
         green: 0,
         red: 0
      },
      text: verificationSite
   };
   element.textSections.push(section);
   section = {
      fontSize: normalFontSize,
      text: ' e informe o código acima ou acesse o link abaixo:',
      style: 'Normal',
      color: {
         blue: 0,
         green: 0,
         red: 0,
         alpha: 100
      }
   };
   element.textSections.push(section);
   manifestMark.elements.push(element);
   verticalOffset += 1.5;

   // Verification link.
   elementHeight = 1.5;
   element = {
      elementType: 'Text',
      relativeContainer: {
         height: elementHeight,
         top: verticalOffset,
         // Full width
         left: 0,
         right: 0
      },
      align: 'Center',
      textSections: []
   };
   section = {
      fontSize: normalFontSize,
      text: verificationLink,
      style: 'Normal',
      color: {
         alpha: 100,
         blue: 255,
         green: 0,
         red: 0
      }
   };
   element.textSections.push(section);
   manifestMark.elements.push(element);
   marks.push(manifestMark);

   return marks;
}

function _getDisplayName(cert) {
   if (cert.pkiBrazil.responsavel) {
      return cert.pkiBrazil.responsavel;
   }
   return cert.pkiBrazil.commonName;
}

function _getDescription(cert) {
   var text = '';
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
   var text = '';
   text += _getDescription(signer.certificate);
   if (signer.signingTime) {
      text += ' em ' + signer.signingTime.format(dateFormat);
   }
   return text;
}

module.exports = router;
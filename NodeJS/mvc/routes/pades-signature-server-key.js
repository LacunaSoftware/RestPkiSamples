var express = require('express');
var request = require('request');
var fs = require('fs');
var crypto = require('crypto');
var uuid = require('node-uuid');
var restPki = require('../lacuna-restpki');
var util = require('../util');

var router = express.Router();
var appRoot = process.cwd();

/**
 * GET /pades-signature-server-key
 *
 * This route performs a PAdES signature using REST PKI and PEM-encoded files
 * for a certificate and for its private key. It renders the signature page.
 */
router.get('/', function(req, res, next) {

   // Read PEM-encoded certificate file for ("Pierre de Fermat").
   var cert = fs.readFileSync('./resources/fermat-cert.pem');

   // If the user was redirected here by the route "upload" (signature with file
   // uploaded by user), the "userfile" URL argument will contain the filename
   // under the "public/app-data" folder. Otherwise (signature with server
   // file), we'll sign a sample document.
   var pdfToSignContent;
   if (req.query.userfile) {
      pdfToSignContent = fs.readFileSync(appRoot + '/public/app-data/' + req.query.userfile);
   } else {
      pdfToSignContent = util.getSamplePdfContent();
   }

   // Request to be sent to REST PKI.
   var restRequest = {

      // Base64-encoding of the PDF to be signed.
      pdfToSign: new Buffer(pdfToSignContent).toString('base64'),

      // Base64-encoding of the signer certificate.
      certificate: new Buffer(cert).toString('base64'),

      // Set the signature policy. For this sample, we'll use the Lacuna Test
      // PKI in order to accept our test certificate used above ("Pierre de
      // Fermat"). This security context should be used FOR DEVELOPMENT PURPOSES
      // ONLY. In production, you'll typically want one of the alternatives
      // below.
      signaturePolicyId: restPki.standardSignaturePolicies.padesBasic,
      securityContextId: restPki.standardSecurityContexts.lacunaTest,

      // Set the visual representation for the signature.
      visualRepresentation: {

         image: {

            // We'll use as background the image previously loaded.
            resource: {
               content: new Buffer(util.getPdfStampContent()).toString('base64'), // Base64-encoding!
               mimeType: 'image/png'
            },

            // (optional) Opacity is an integer from 0 to 100 (0 is completely
            // transparent, 100 is completely opaque). If omitted, 100 is
            // assumed.
            opacity: 50,

            // (optional) Specify the image horizontal alignment. Possible
            // values are 'Left', 'Center' and 'Right'. If omitted, 'Center'
            // is assumed.
            horizontalAlign: 'Right',

            // (optional) Specify the image vertical alignment. Possible values
            // are 'Top', 'Center' and 'Bottom'. If omitted, 'Center' is
            // assumed.
            verticalAlign: 'Center'

         },

         text: {

            // The tags {{name}} and {{national_id}} will be substituted
            // according to the user's certificate:
            //
            //  name        : Full name of the signer;
            //  national_id : If the certificate is ICP-Brasil, contains the
            //                signer's CPF.
            //
            // For a full list of the supported tags, see: https://github.com/LacunaSoftware/RestPkiSamples/blob/master/PadesTags.md
            text: 'Signed by {{name}} ({{national_id}})',

            // Specify that the signing time should also be rendered.
            includeSigningTime: true,

            // Optionally set the horizontal alignment of the text ('Left' or
            // 'Right'), if not set the default is Left.
            horizontalAlign: 'Left',

            // Optionally set the container within the signature rectangle on
            // which to place the text. By default, the text can occupy the
            // entire rectangle (how much of the rectangle the text will
            // actually fill depends on the length and font size). Below, we
            // specify that the text should respect a right margin of 1.5 cm.
            container: {
               left: 0,
               top: 0,
               right: 1.5,
               bottom: 0
            }

         },

         position: {

            // Page on which to draw the visual representation. Negative values
            // are counted from the end of the document (-1 is last page). Zero
            // means the signature will be placed on a new page appended to the
            // end of the document.
            pageNumber: -1,

            // Measurement units of the values below ('Centimeters' or
            // 'PdfPoints').
            measurementUnits: 'Centimeters',

            // Automatic placing of signatures within a container, one after the
            // other.
            auto: {

               // Specification of the container where the signatures will be
               // placed.
               container: {
                  // Specifying left and right (but no width) results in a
                  // variable-width container with the given margins.
                  left: 1.5,
                  right: 1.5,
                  // Specifying bottom and height (but no top) results in a
                  // bottom-aligned fixed-height container.
                  bottom: 1.5,
                  height: 3
               },

               // Specification of the size of each signature rectangle.
               signatureRectangleSize: {
                  width: 7,
                  height: 3
               },

               // The signatures will be placed in the container side by side.
               // If there's no room left, the signatures will "wrap" to the
               // next row. The value below specifies the vertical distance
               // between rows.
               rowSpacing: 1.5
            }
         }
      }
   };

   // Call the action POST Api/PadesSignatures on REST PKI, which initiates the
   // signature.
   request.post(util.endpoint + 'Api/PadesSignatures', {

      json: true,
      headers: {'Authorization': 'Bearer ' + util.accessToken},
      body: restRequest

   }, onSignatureStarted);

   // This function will be executed as callback of the POST request that
   // initializes the signature on REST PKI. The response will be checked and if
   // an error occurred, it will be rendered.
   function onSignatureStarted(err, restRes, body) {

      if (restPki.checkResponse(err, restRes, body, next)) {

         // Read PEM-encoded private-key file for ("Pierre de Fermat").
         var pkey = fs.readFileSync('./resources/fermat-pkey.pem', 'binary');

         // Get signature algorithm from the digestAlgorithmOid. It will be used
         // by the crypto library to perform the signature.
         var signatureAlgorithm;
         switch (restRes.body.digestAlgorithmOid) {
            case '1.2.840.113549.2.5':
               signatureAlgorithm = 'RSA-MD5';
               break;
            case '1.3.14.3.2.26':
               signatureAlgorithm = 'RSA-SHA1';
               break;
            case '2.16.840.1.101.3.4.2.1':
               signatureAlgorithm = 'RSA-SHA256';
               break;
            case '2.16.840.1.101.3.4.2.2':
               signatureAlgorithm = 'RSA-SHA384';
               break;
            case '2.16.840.1.101.3.4.2.3':
               signatureAlgorithm = 'RSA-SHA512';
               break;
            default:
               signatureAlgorithm = null;
         }

         // Create a new signature, setting the algorithm that will be used.
         var sign = crypto.createSign(signatureAlgorithm);

         // Set the data that will be signed.
         sign.write(new Buffer(restRes.body.toSignData, 'base64'));
         sign.end();

         // Perform the signature and receiving Base64-enconding of the
         // signature.
         var signature = sign.sign({key: pkey, passphrase: '1234'}, 'base64');

         // Call the action POST Api/PadesSignatures/{token}/SignedBytes on
         // REST PKI, which finalizes the signature process and returns the
         // signed PDF.
         request.post(util.endpoint + 'Api/PadesSignatures/' + restRes.body.token + '/SignedBytes', {

            json: true,
            headers: {'Authorization': 'Bearer ' + util.accessToken},
            body: {'signature': signature}

         }, onSignatureCompleted);
      }
   }

   // This function will be executed as callback of the POST request that
   // finalizes the signature on REST PKI. The response will be checked and if
   // an error occurred, it will be rendered.
   function onSignatureCompleted(err, restRes, body) {

      if (restPki.checkResponse(err, restRes, body, next)) {

         // At this point, you'd typically store the signed PDF on your
         // database. For demonstration purposes, we'll store the PDF on a
         // temporary folder publicly accessible and render a link to it.
         var signedPdfContent = new Buffer(restRes.body.signedPdf, 'base64');
         var filename = uuid.v4() + '.pdf';
         var appDataPath = appRoot + '/public/app-data/';
         if (!fs.existsSync(appDataPath)) {
            fs.mkdirSync(appDataPath);
         }
         fs.writeFileSync(appDataPath + filename, signedPdfContent);

         res.render('pades-signature-complete', {
            signedFile: filename,
            signerCert: restRes.body.certificate
         });

      }
   }

});

module.exports = router;
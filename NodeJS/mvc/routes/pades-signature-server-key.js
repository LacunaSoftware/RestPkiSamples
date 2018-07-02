const express = require('express');
const crypto = require('crypto');
const fs = require('fs');
const uuid = require('node-uuid');
const {
   PadesSignatureStarter,
   PadesSignatureFinisher,
   PadesMeasurementUnits,
   StandardSignaturePolicies,
   StandardSecurityContexts
} = require('restpki-client');

const { Util } = require('../util');
const { PadesVisualElements } = require('../pades-visual-elements');

let router = express.Router();
let appRoot = process.cwd();

/**
 * GET /pades-signature-server-key
 *
 * This route performs a PAdES signature using REST PKI and PEM-encoded files
 * for a certificate and for its private key. It renders the signature page.
 */
router.get('/', function(req, res, next) {

   // Read PEM-encoded certificate file for ("Pierre de Fermat").
   let cert = fs.readFileSync('./resources/fermat-cert.pem');

   // Get an instance of the PadesSignatureStarter class, responsible for
   // receiving the signature elements and start the signature process.
   let signatureStarter = new PadesSignatureStarter(Util.getRestPkiClient());

   // If the user was redirected here by the route "upload" (signature with file
   // uploaded by user), the "userfile" URL argument will contain the filename
   // under the "public/app-data" folder. Otherwise (signature with server
   // file), we'll sign a sample document.
   if (req.query.userfile) {
      signatureStarter.pdfToSign = appRoot + '/public/app-data/' + req.query.userfile;
   } else {
      signatureStarter.pdfToSign = Util.getSamplePdfPath();
   }

   // Set the signature policy.
   signatureStarter.signaturePolicy = StandardSignaturePolicies.PADES_BASIC;

   // Set the security context to be used to determine trust in the certificate
   // chain.
   signatureStarter.securityContext = StandardSecurityContexts.LACUNA_TEST;

   // Set the signer certificate.
   signatureStarter.signerCertificate = cert;

   // Set the unit of measurements used to edit the PDF marks and visual
   // representations.
   signatureStarter.measurementUnits = PadesMeasurementUnits.CENTIMETERS;

   // Set the visual representation to the signature. We have encapsulated this
   // code (on util-pades.js) to be used on various PAdES examples.
   PadesVisualElements.getVisualRepresentation()
   .then((visualRepresentation) => {

      // Call the start() method.
      signatureStarter.visualRepresentation = visualRepresentation;
      return signatureStarter.start();

   })
   .then((signatureParams) => {

      // Read PEM-encoded private-key file for ("Pierre de Fermat").
      let pkey = fs.readFileSync('./resources/fermat-pkey.pem', 'binary');

      // Get signature algorithm from the digestAlgorithmOid. It will be used
      // by the crypto library to perform the signature.
      let signatureAlgorithm;
      switch (signatureParams.digestAlgorithmOid) {
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
      let sign = crypto.createSign(signatureAlgorithm);

      // Set the data that will be signed.
      sign.write(new Buffer(signatureParams.toSignData, 'base64'));
      sign.end();

      // Perform the signature and receiving Base64-enconding of the
      // signature.
      let signature = sign.sign({ key: pkey, passphrase: '1234' }, 'base64');

      // Get an instance of the PadesSignatureFinisher class, responsible for
      // completing the signature process.
      let signatureFinisher = new PadesSignatureFinisher(Util.getRestPkiClient());

      // Set the token.
      signatureFinisher.token = signatureParams.token;

      // Set the signature.
      signatureFinisher.signature = signature;

      // Call the finish() method, which finalizes the signature process and
      // returns the SignatureResult object. This is method returns a promise
      // that should be handled.
      return signatureFinisher.finish();

   })
   .then((result) => {

      // The "certificate" property of the SignatureResult object contains
      // information about the certificate used by the user to sign the file.
      let signerCert = result.certificate;

      // At this point, you'd typically store the signed PDF on you database.
      // For demonstration purposes, we'll store the PDF on a temporary folder
      // publicly accessible and render a link to it.

      Util.createAppData(); // Make sure the "app-data" folder exists (util.js).
      let filename = uuid.v4() + '.pdf';

      // The SignatureResult object has functions for writing the signature file
      // to a local life (writeToFile()) and to get its raw contents
      // (getContent()). For large files, use writeToFile() in order to avoid
      // memory allocation issues.
      result.writeToFileSync(appRoot + '/public/app-data/' + filename);

      res.render('pades-signature-server-key', {
         signedFile: filename,
         signerCert: signerCert
      });

   })
   .catch((err) => next(err));

});

module.exports = router;
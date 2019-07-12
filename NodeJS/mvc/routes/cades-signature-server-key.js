const express = require('express');
const crypto = require('crypto');
const fs = require('fs');
const uuidv4 = require('uuid/v4');
let { CadesSignatureStarter, CadesSignatureFinisher, StandardSignaturePolicies, StandardSecurityContexts } = require('restpki-client');

let { Util } = require('../util');

let router = express.Router();
let appRoot = process.cwd();

/**
 * GET /cades-signature-server-key
 *
 * This route performs a CAdES signature using REST PKI and PEM-encoded files
 * for a certificate and for its private key. It renders the signature page.
 */
router.get('/', function(req, res, next) {

   // Read PEM-encoded certificate file for ("Pierre de Fermat")
   let cert = fs.readFileSync('./resources/fermat-cert.pem');

   // Get an instance of the CadesSignatureStarter class, responsible for
   // receiving the signature elements and start the signature process.
   let signatureStarter = new CadesSignatureStarter(Util.getRestPkiClient(res.locals.environment));

   if (req.query.userfile) {

      // If the URL argument "userfile" is filled, it means the user was
      // redirected here by the route "upload" (signature with file uploaded by
      // user). We'll set the path of the file to be signed, which was saved
      // in the "public/app-data" folder by the route upload.
      signatureStarter.fileToSign = appRoot + '/public/app-data/' + req.query.userfile;

   } else if (req.query.cmsfile) {

      /*
       * If the URL argument "cmsfile" is filled, the user has asked to co-sign
       * a previously signed CMS. We'll set the path to the CMS to be co-signed,
       * which was previously saved in the "public/app-data" folder by complete
       * action. Not two things:
       *
       *    1. The CMS to be co-signed must be set using the setters for the
       *       "cmsToCoSign" or "cmsToCoSignContent" properties, not the setters
       *       for "fileToSign" or "fileToSignContent".
       *
       *    2. Since we're creating CMSs with encapsulated content (see property
       *       encapsulateContent's setting below), we don't need to set the
       *       content to be signed, REST PKI will get the content from CMS
       *       being co-signed.
       */
      signatureStarter.cmsToCoSign = appRoot + '/public/app-data/' + req.query.cmsfile;

   } else {

      // If both userfile and cms file are null/undefined, this is the
      // "signature with server file" case. We'll set the path to the sample
      // document.
      signatureStarter.fileToSign = Util.getSamplePdfPath();
   }

   // Set the signature policy.
   signatureStarter.signaturePolicy = StandardSignaturePolicies.PKI_BRAZIL_CADES_ADR_BASICA;

   // Set the security context to be used to determine trust in the certificate
   // chain.
   signatureStarter.securityContext = StandardSecurityContexts.LACUNA_TEST;

   // Set the signer certificate.
   signatureStarter.signerCertificate = cert;

   // Optionally, set whether the content should be encapsulated in the
   // resulting CMS. If this parameter is omitted, the following rules apply:
   // - If no CmsToCoSign is given, the resulting CMS will include the content.
   // - If a CmsToCoSign is given, the resulting CMS will include the content
   //   if and only if the CmsToCoSign also includes the content.
   signatureStarter.encapsulateContent = true;

   // Call the start() method.
   signatureStarter.start()
   .then((signatureParams) => {

      // Read PEM-encoded private-key file for ("Pierre de Fermat").
      let pkey = fs.readFileSync('./resources/fermat-pkey.pem', 'binary');

      // Create a new signature, setting the algorithm that will be used.
      let sign = crypto.createSign(signatureParams.cryptoSignatureAlgorithm);

      // Set the data that will be signed.
      sign.write(new Buffer(signatureParams.toSignData, 'base64'));
      sign.end();

      // Perform the signature and receiving Base64-enconding of the
      // signature.
      let signature = sign.sign({ key: pkey, passphrase: '1234' }, 'base64');

      // Get an instance of the CadesSignatureFinisher class, responsible for
      // completing the signature process.
      let signatureFinisher = new CadesSignatureFinisher(Util.getRestPkiClient());

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
      let filename = uuidv4() + '.p7s';

      // The SignatureResult object has functions for writing the signature file
      // to a local life (writeToFile()) and to get its raw contents
      // (getContent()). For large files, use writeToFile() in order to avoid
      // memory allocation issues.
      result.writeToFileSync(appRoot + '/public/app-data/' + filename);

      res.render('cades-signature-server-key', {
         signedFile: filename,
         signerCert: signerCert
      });

   })
   .catch((err) => next(err));

});

module.exports = router;
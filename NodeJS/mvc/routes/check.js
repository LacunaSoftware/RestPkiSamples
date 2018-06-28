const express = require('express');
const { PadesSignatureExplorer, StandardSignaturePolicies } = require('restpki-client');

const { Util } = require('../util');
const { StorageMock } = require('../storage-mock');

let router = express.Router();
let appRoot = process.cwd();

/*
 * GET /check
 *
 * This route submits a PDF file to REST PKI for inspection of its signatures
 * and renders the results.
 */
router.get('/', function(req, res, next) {

   // On printer-friendly-version, we stored the unformatted version of
   // the verification code (without hyphens) but used the formatted version
   // (with hyphens) on the printer-friendly PDF. Now, we remove the hyphen
   // before looking it up.
   let verificationCode = Util.parseVerificationCode(req.query.code);

   // Our demo only works if a userfile is given to work with
   let fileId = StorageMock.lookupVerificationCode(req.session, verificationCode);

   if (!fileId) {
      // Invalid code given!
      res.status(404).send('Not found');
      return;
   }

   // Open and validate signature with Rest PKI.
   let sigExplorer = new PadesSignatureExplorer(Util.getRestPkiClient());

   // Se the PDF file to be inspected.
   sigExplorer.signatureFile = appRoot + '/public/app-data/' + fileId;

   // Specify that we want to validate the signatures in the file, not only
   // inspect them.
   sigExplorer.validate = true;

   // Specify the signature policy for signature validation. On this sample, we
   // will accept any valid PAdES signature as long as the signer is trusted by
   // the security context.
   sigExplorer.defaultSignaturePolicyId = StandardSignaturePolicies.PADES_BASIC;

   // Specify the security context to be used to determine trust in the
   // certificate chain. We have encapsulated the security context choice on
   // util.js.
   sigExplorer.securityContextId = Util.getSecurityContextId();

   // Call the open() method, which returns the signature file's information.
   sigExplorer.open()
   .then((signature) => {

      // Render the check page.
      res.render('check', {
         signature: signature,
         fileId: fileId
      });

   })
   .catch((err) => next(err));

});

module.exports = router;
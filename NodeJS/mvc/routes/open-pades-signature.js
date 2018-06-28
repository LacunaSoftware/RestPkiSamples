const express = require('express');
const { PadesSignatureExplorer, StandardSignaturePolicies} = require('restpki-client');

const { Util } = require('../util');

let router = express.Router();
let appRoot = process.cwd();

/*
 * GET /open-pades-signature
 * 
 * This route submits a PDF file to REST PKI for inspection of its signatures
 * and renders the results.
 */
router.get('/', function(req, res, next) {

   // Our demo only works if a userfile is given to work with.
   if (!req.query.userfile) {
      res.status(404).send('Not found');
      return;
   }

   // Get an instance of the PadesSignatureExplorer class, used to open/validate
   // PDF signatures.
   let sigExplorer = new PadesSignatureExplorer(Util.getRestPkiClient());

   // Set the PDF file to be inspected.
   sigExplorer.signatureFile = appRoot + '/public/app-data/' + res.query.userfile;

   // Specify that we want to validate the signatures in the file, not only
   // inspect them.
   sigExplorer.validate = true;

   // Specify the signature policy for signature validation. On this sample,
   // we will accept any valid PAdES signature as long as the signer is trusted
   // by the security context.
   sigExplorer.defaultSignaturePolicyId = StandardSignaturePolicies.PADES_BASIC;

   // Specify the security context to be used to determine trust in the
   // certificate chain. We have encapsulated the security context choice on
   // util.js.
   sigExplorer.securityContextId = Util.getSecurityContextId();

   // Call the open() method, which returns the signature file's information.
   sigExplorer.open()
   .then((signature) => {

      // Render the signature opening page.
      res.render('open-pades-signature', {
         signature: signature
      });

   })
   .catch((err) => next(err));

});

module.exports = router;
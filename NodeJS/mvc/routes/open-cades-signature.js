const express = require('express');
const { CadesSignatureExplorer, StandardSignaturePolicyCatalog } = require('restpki-client');

const { Util } = require('../util');

let router = express.Router();
let appRoot = process.cwd();

/*
 * GET /open-cades-signature
 * 
 * This route submits a CAdES signature file to REST PKI for inspection of its
 * signatures and renders the results.
 */
router.get('/', function(req, res, next) {

   // Our demo only works if a userfile is given to work with.
   if (!req.query.userfile) {
      res.status(404).send('Not found');
      return;
   }

   // Get an instance of the CadesSignatureExplorer class, used to open/validate
   // CAdES signatures.
   let sigExplorer = new CadesSignatureExplorer(Util.getRestPkiClient());

   // Set the CAdES signature file to be inspected.
   sigExplorer.signatureFile = appRoot + '/public/app-data' + req.query.userfile;

   // Specify that we want to validate the signatures in the file, not only
   // inspect them.
   sigExplorer.validate = true;

   // Specify the signature policy for signature validation. On this sample,
   // we will accept only 100%-compliant ICP-Brasil signatures.
   sigExplorer.acceptableExplicitPolicies = StandardSignaturePolicyCatalog.getPkiBrazilCades();

   // Specify the security context to be used to determine trust in the
   // certificate chain. We have encapsulated the security context choice on
   // util.js.
   sigExplorer.securityContextId = Util.getSecurityContextId();

   // Call the open() method, which returns the signature file's information.
   sigExplorer.open()
   .then((signature) => {

      // Render the signature opening page.
      res.render('open-cades-signature', {
         signature: signature
      });

   })
   .catch((err) => next(err));

});

module.exports = router;
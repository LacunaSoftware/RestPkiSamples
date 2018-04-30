var express = require('express');
var request = require('request');
var fs = require('fs');
var moment = require('moment');

var restPki = require('../lacuna-restpki');
var util = require('../util');

var router = express.Router();
var appRoot = process.cwd();

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

   // Request to be sent to REST PKI.
   var openRequest = {

      // Specify that we want to validate the signatures in the file, not only
      // inspect them.
      validate: true,

      // Specify the signature policy for signature validation. On this sample,
      // we will accept only 100%-compliant ICP-Brasil signatures.
      acceptableExplicitPolicies: [
         restPki.standardSignaturePolicies.pkiBrazilCadesAdrBasica,
         restPki.standardSignaturePolicies.pkiBrazilCadesAdrTempo,
         restPki.standardSignaturePolicies.pkiBrazilCadesAdrCompleta
      ],

      // Specify file to be inspected.
      file: {

         // Inform the file content encoded in Base64.
         content: new Buffer(fs.readFileSync(appRoot + '/public/app-data/' + req.query.userfile)).toString('base64')
      },

      // Specify if wants to extract encapsulated content.
      extractEncapsulatedContent: false
   };

   // Call the action POST Api/CadesSignatures/Open, which open/validate the
   // signature.
   request.post(util.endpoint + 'Api/CadesSignatures/Open', {
      json: true,
      headers: {'Authorization': 'Bearer ' + util.accessToken},
      body: openRequest
   }, function(err, restRes, body) {

      if (restPki.checkResponse(err, restRes, body, next)) {

         // Parse output
         var signature = restRes.body;
         signature.signers.forEach(function(signer) {
            signer.validationResults = new restPki.ValidationResults(signer.validationResults);
            if (signer.signingTime) {
               // Format date using moment package.
               signer.signingTime = moment(signer.signingTime).format('DD/MM/YYYY HH:mm');
            }
            if (signer.certificate && signer.certificate.pkiBrazil) {
               signer.certificate.pkiBrazil.cpfFormatted = util.formatCpf(signer.certificate.pkiBrazil.cpf);
               signer.certificate.pkiBrazil.cnpjFormatted = util.formatCnpj(signer.certificate.pkiBrazil.cnpj);
            }
         });

         // Render the signature page.
         res.render('open-cades-signature', {
            signature: signature
         });
      }
   });

});

module.exports = router;
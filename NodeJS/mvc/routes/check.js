var express = require('express');
var request = require('request');
var fs = require('fs');
var moment = require('moment');

var restPki = require('../lacuna-restpki');
var util = require('../util');
var storageMock = require('../storage-mock');

var router = express.Router();
var appRoot = process.cwd();

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
   var verificationCode = util.parseVerificationCode(req.query.code);

   // Our demo only works if a userfile is given to work with
   var fileId = storageMock.lookupVerificationCode(req.session, verificationCode);

   if (!fileId) {
      // Invalid code given!
      res.status(404).send('Not found');
      return;
   }

   // Request to be sent to REST PKI.
   var openRequest = {

      // Specify that we want to validate the signatures in the file, not only
      // inspect them.
      validate: true,

      // Specify the signature policy for signature validation. On this sample,
      // we will accept any PAdES signature as long as the signer has an
      // ICP-Brasil certificate.
      defaultSignaturePolicyId: restPki.standardSignaturePolicies.padesBasicWithPkiBrazilCerts,

      // Specify file to be inspected.
      file: {
         // Inform the file content encoded in Base64.
         content: new Buffer(fs.readFileSync(appRoot + '/public/app-data/' + fileId)).toString('base64')
      }
   };

   // Call the action POST Api/PadesSignatures/Open, which open/validate the
   // signature.
   request.post(util.endpoint + 'Api/PadesSignatures/Open', {
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

         // Render the signature page
         res.render('check', {
            signature: signature,
            fileId: fileId
         });
      }
   });

});

module.exports = router;
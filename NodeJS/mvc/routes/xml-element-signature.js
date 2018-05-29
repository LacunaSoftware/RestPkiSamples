var express = require('express');
var request = require('request');
var fs = require('fs');
var uuid = require('node-uuid');
var restPki = require('../lacuna-restpki');
var util = require('../util');

var router = express.Router();
var appRoot = process.cwd();

/*
 * GET /xml-element-signature
 *
 * This route initiates a XML element signature using REST PKI and renders the
 * signature page.
 */
router.get('/', function(req, res, next) {

   // Request to be sent to REST PKI.
   var restRequest = {

      // Set the XML to be signed, a sample XML Document.
      xml: new Buffer(util.getSampleNFe()).toString('base64'),

      // Set the ID of the element to be signed.
      elementToSignId: 'NFe35141214314050000662550010001084271182362300',

      // Set the signature policy.
      signaturePolicyId: restPki.standardSignaturePolicies.pkiBrazilNFePadraoNacional

      // Optionally, set a SecurityContext to be used to determine trust in the
      // certificate chain. Since we're using the
      // StandardXmlSignaturePolicies.PkiBrazil.NFePadraoNacional policy, the
      // security context will default to PKI Brazil (ICP-Brasil):

      // securityContextId: restPki.standardSecurityContexts.pkiBrazil

      // Note: By changing the SecurityContext above you can accept only
      // certificates from a certain PKI Set a SecurityContext to be used to
      // determine trust in the certificate chain
   };

   // Call the action POST Api/XmlSignatures/XmlElementSignature on REST PKI,
   // which initiates the signature.
   request.post(util.endpoint + 'Api/XmlSignatures/XmlElementSignature', {

      json: true,
      headers: {'Authorization': 'Bearer ' + util.accessToken},
      body: restRequest

   }, function(err, restRes, body) {

      if (restPki.checkResponse(err, restRes, body, next)) {

         // This operation yields the token, a 43-character case-sensitive
         // URL-safe string, which identifies this signature process. We'll use
         // this value to call the signWithRestPki() method on the Web
         // component (see view 'xml-element-signature') and also to complete
         // the signature after the form is submitted. This should not be
         // mistaken with the API access token.
         var token = restRes.body.token;

         // The token acquired can only be used for a single signature attempt.
         // In order to retry the signature it is necessary to get a new token.
         // This can be a problem if the user uses the back button of the
         // browser, since the browser might show a cached page that we rendered
         // previously, with a now stale token. To prevent this from happening,
         // we set some response headers specifying that the page should not be
         // cached.
         util.setExpiredPage(res);

         // Render the signature page
         res.render('xml-element-signature', {
            token: token,
            userfile: req.query.userfile
         });
      }

   });
});

/*
 * POST /xml-element-signature
 *
 * This route receives the form submission from the view
 * 'xml-element-signature'. We'll call REST PKI to complete the signature.
 */
router.post('/', function(req, res, next) {

   // Retrieve the token from the URL
   var token = req.body.token;

   // Call the action POST Api/XmlSignatures/{token}/Finalize on REST PKI,
   // which finalizes the signature process and returns the signed PDF
   request.post(util.endpoint + 'Api/XmlSignatures/' + token + '/Finalize', {

      json: true,
      headers: {'Authorization': 'Bearer ' + util.accessToken}

   }, function(err, restRes, body) {

      if (restPki.checkResponse(err, restRes, body, next)) {

         var signedXmlContent = new Buffer(restRes.body.signedXml, 'base64');

         // At this point, you'd typically store the signed PDF on your
         // database. For demonstration purposes, we'll store the PDF on a
         // temporary folder publicly accessible and render a link to it.
         var filename = uuid.v4() + '.xml';
         var appDataPath = appRoot + '/public/app-data/';
         if (!fs.existsSync(appDataPath)) {
            fs.mkdirSync(appDataPath);
         }
         fs.writeFileSync(appDataPath + filename, signedXmlContent);
         res.render('xml-signature-complete', {
            signedFile: filename,
            signerCert: restRes.body.certificate
         });

      }

   });
});

module.exports = router;

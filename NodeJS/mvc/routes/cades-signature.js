var express = require('express');
var request = require('request');
var fs = require('fs');
var uuid = require('node-uuid');
var restPki = require('../lacuna-restpki');
var util = require('../util');

var router = express.Router();
var appRoot = process.cwd();

/*
 * GET /cades-signature
 *
 * This route initiates a CAdES signature using REST PKI and renders the
 * signature page.
 *
 * All CAdES signature examples converge to this action, but with different URL
 * arguments:
 *
 * 1. Signature with a server file               : no arguments filled
 * 2. Signature with a file uploaded by the user : "userfile" filled
 * 3. Co-signature of a previously signed CMS    : "cmsfile" filled
 */
router.get('/', function(req, res, next) {

   // Request to be sent to REST PKI.
   var restRequest = {

      // Optionally, set whether the content should be encapsulated in the
      // resulting CMS. If this parameter is omitted, the following rules
      // apply:
      //
      // - If no CmsToCoSign is given, the resulting CMS will include the
      //   content.
      // - If a CmsToCoSign is given, the resulting CMS will include the
      //   content, if and only if, the CmsToCoSign also includes the content.
      encapsulateContent: true,

      // Set the signature policy.
      signaturePolicyId: restPki.standardSignaturePolicies.pkiBrazilCadesAdrBasica

      // Optionally, set a SecurityContext to be used to determine trust in the
      // certificate chain:

      // securityContextId: restPki.standardSecurityContexts.pkiBrazil

      // Note: Depending on the signature policy chosen above, setting the
      // security context may be mandatory (this is not the case for
      // ICP-Brasil policies, which will automatically use the PkiBrazil
      // security context if none is passed)
   };

   if (req.query.userfile) {

      // If the user was redirected here by the route "upload" (signature with
      // file uploaded by user), the "userfile" URL argument will contain the
      // filename under the "public/app-data" folder.
      restRequest['contentToSign'] = new Buffer(fs.readFileSync(appRoot + '/public/app-data/' + req.query.userfile)).toString('base64');

   } else if (req.query.cmsfile) {

      /*
       * If the URL argument "cmsfile" is filled, the user has asked to co-sign
       * a previously signed CMS. We'll set the path to the CMS to be co-signed,
       * which was previously saved in the "app-data". Since we're creating CMSs
       * with encapsulated content (see call to setEncapsulateContent below), we
       * don't need to set the content to be signed, REST PKI will get the
       * content from the CMS being co-signed.
       */
      restRequest['cmsToCoSign'] = new Buffer(fs.readFileSync(appRoot + '/public/app-data/' + req.query.cmsfile)).toString('base64');

   } else {

      // If both userfile and cms file are null/undefined, this is the
      // "signature with server file" case. We'll set the path to the sample
      // document.
      restRequest['contentToSign'] = new Buffer(util.getSamplePdfContent()).toString('base64');
   }

   // Call the action POST Api/CadesSignatures on REST PKI, which initiates the
   // signature.
   request.post(util.endpoint + 'Api/CadesSignatures', {

      json: true,
      headers: {'Authorization': 'Bearer ' + util.accessToken},
      body: restRequest

   }, function(err, restRes, body) {

      if (restPki.checkResponse(err, restRes, body, next)) {

         // This operation yields the token, a 43-character case-sensitive
         // URL-safe string, which identifies this signature process. We'll use
         // this value to call the signWithRestPki() method on the Web PKI
         // component (see view 'cades-signature') and also to complete the
         // signature after the form is submitted. This should not be mistaken
         // with the API access token.
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
         res.render('cades-signature', {
            token: token,
            userfile: req.query.userfile,
            cmsfile: req.query.cmsfile
         });
      }

   });
});

/*
 * POST /cades-signature
 *
 * This route receives the form submission from the view 'cades-signature'.
 * We'll call REST PKI to complete the signature.
 */
router.post('/', function(req, res, next) {

   // Retrieve the token from the URL.
   var token = req.body.token;

   // Call the action POST Api/CadesSignatures/{token}/Finalize on REST PKI,
   // which finalizes the signature process and returns the signed PDF.
   request.post(util.endpoint + 'Api/CadesSignatures/' + token + '/Finalize',
       {

          json: true,
          headers: {'Authorization': 'Bearer ' + util.accessToken}

       }, function(err, restRes, body) {

          if (restPki.checkResponse(err, restRes, body, next)) {

             var signedContent = new Buffer(restRes.body.cms, 'base64');

             // At this point, you'd typically store the signed PDF on your
             // database. For demonstration purposes, we'll store the PDF on a
             // temporary folder publicly accessible and render a link to it.
             var filename = uuid.v4() + '.p7s';
             var appDataPath = appRoot + '/public/app-data/';
             if (!fs.existsSync(appDataPath)) {
                fs.mkdirSync(appDataPath);
             }
             fs.writeFileSync(appDataPath + filename, signedContent);
             res.render('cades-signature-complete', {
                signedFile: filename,
                signerCert: restRes.body.certificate
             });

          }
       });
});

module.exports = router;

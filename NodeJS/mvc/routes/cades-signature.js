const express = require('express');
const uuidv4 = require('uuid/v4');
const { CadesSignatureStarter, CadesSignatureFinisher, StandardSignaturePolicies } = require('restpki-client');

const { Util } = require('../util');

let router = express.Router();
let appRoot = process.cwd();

/*
 * GET /cades-signature
 *
 * This route initiates a CAdES signature using REST PKI and renders the
 * signature page.
 *
 * All CAdES signature examples converge to this action, but with different URL
 * arguments:
 *
 *    1. Signature with a server file               : no arguments filled
 *    2. Signature with a file uploaded by the user : "userfile" filled
 *    3. Co-signature of a previously signed CMS    : "cmsfile" filled
 */
router.get('/', function(req, res, next) {

   // Get an instance of the CadesSignatureStarter class, responsible for
   // receiving the signature elements and start the signature process.
   let signatureStarter = new CadesSignatureStarter(Util.getRestPkiClient());

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
   // chain. We have encapsulated the security context choice on util.js.
   signatureStarter.securityContext = Util.getSecurityContextId(res.locals.environment);

   // Optionally, set whether the content should be encapsulated in the
   // resulting CMS. If this parameter is omitted, the following rules apply:
   // - If no CmsToCoSign is given, the resulting CMS will include the content.
   // - If a CmsToCoSign is given, the resulting CMS will include the content
   //   if and only if the CmsToCoSign also includes the content.
   signatureStarter.encapsulateContent = true;

   // Call the startWithWebPki() method, which initiates the signature. This
   // yields the token, a 43-character case-sensitive URL-safe string, which
   // identifies this signature process. We'll use this value to call the
   // signWithRestPki() method on the WebPKI component
   // (see public/js/signature-form.js) and also to complete the signature
   // after the form is submitted (see post method). This should not be mistaken
   // with the API access token.
   signatureStarter.startWithWebPki()
   .then((result) => {

      // The token acquired above can only be used for a single signature
      // attempt. In order to retry the signature it is necessary to get a new
      // token. This can be a problem if the user uses the back button of the
      // browser, since the browser might show a cached page that we rendered
      // previously, with a now stale token. To prevent this from happening, we
      // call the function setExpiredPage(), located in util.js, which sets HTTP
      // headers to prevent caching of the page.
      Util.setExpiredPage(res);

      // Render the signature page
      res.render('cades-signature', {
         token: result.token,
         userfile: req.query.userfile,
         cmsfile: req.query.cmsfile
      });

   })
   .catch((err) => next(err));

});

/*
 * POST /cades-signature
 *
 * This route receives the form submission from the view 'cades-signature'.
 * We'll call REST PKI to complete the signature.
 */
router.post('/', function(req, res, next) {

   // Get an instance of the CadesSignatureFinisher class, responsible for
   // completing the signature process.
   let signatureFinisher = new CadesSignatureFinisher(Util.getRestPkiClient());

   // Set the token.
   signatureFinisher.token = req.body.token;

   // Call the finish() method, which finalizes the signature process and
   // returns the SignatureResult object.
   signatureFinisher.finish()
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

      // Render the result page.
      res.render('cades-signature-complete', {
         signedFile: filename,
         signerCert: signerCert
      });

   })
   .catch((err) => next(err));

});

module.exports = router;

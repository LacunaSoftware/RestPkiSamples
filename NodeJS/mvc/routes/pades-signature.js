const express = require('express');
const uuidv4 = require('uuid/v4');
const { PadesSignatureStarter, PadesSignatureFinisher, PadesMeasurementUnits, StandardSignaturePolicies } = require('restpki-client');

const { Util } = require('../util');
const { PadesVisualElements } = require('../pades-visual-elements');

let router = express.Router();
let appRoot = process.cwd();

/*
 * GET /pades-signature
 *
 * This route initiates a PAdES signature using REST PKI and renders the
 * signature page.
 *
 * Both PAdES signature examples, with a server file and with a file uploaded by
 * the user, use this route. The difference is that, when the file is uploaded
 * by the user, the route is called with a URL argument named "userfile".
 */
router.get('/', function(req, res, next) {

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
   // chain. We have encapsulated the security context choice on util.js.
   signatureStarter.securityContext = Util.getSecurityContextId(res.locals.environment);

   // Set the unit of measurements used to edit the PDF marks and visual
   // representations.
   signatureStarter.measurementUnits = PadesMeasurementUnits.CENTIMETERS;

   // Set the visual representation to the signature. We have encapsulated this
   // code (on pades-visual-elements.js) to be used on various PAdES examples.
   PadesVisualElements.getVisualRepresentation()
   .then((visualRepresentation) => {

      signatureStarter.visualRepresentation = visualRepresentation;

      // Call the startWithWebPki() method, which initiates the signature. This
      // yields the token, a 43-character case-sensitive URL-safe string, which
      // identifies this signature process. We'll use this value to call the
      // signWithRestPki() method on the WebPKI component
      // (see public/js/signature-form.js) and also to complete the siganture after
      // the form is submitted (see post method). This should not be mistaken with
      // the API access token.
      return signatureStarter.startWithWebPki();

   })
   .then((result) => {

      // The token acquired can only be used for a single signature attempt.
      // In order to retry the signature it is necessary to get a new token.
      // This can be a problem if the user uses the back button of the
      // browser, since the browser might show a cached page that we rendered
      // previously, with a now stale token. To prevent this from happening,
      // we set some response headers specifying that the page should not be
      // cached.
      Util.setExpiredPage(res);

      // Render the signature page
      res.render('pades-signature', {
         token: result.token,
         userfile: req.query.userfile
      });

   })
   .catch(err => next(err));
});

/*
 * POST /pades-signature
 *
 * This route receives the form submission from the view 'pades-signature'.
 * We'll call REST PKI to complete the signature.
 */
router.post('/', function(req, res, next) {

   // Get an instance of the PadesSignatureFinisher class, responsible for
   // completing the signature process.
   let signatureFinisher = new PadesSignatureFinisher(Util.getRestPkiClient());

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
      let filename = uuidv4() + '.pdf';

      // The SignatureResult object has functions for writing the signature file
      // to a local life (writeToFile()) and to get its raw contents
      // (getContent()). For large files, use writeToFile() in order to avoid
      // memory allocation issues.
      result.writeToFileSync(appRoot + '/public/app-data/' + filename);

      res.render('pades-signature-complete', {
         signedFile: filename,
         signerCert: signerCert
      });

   })
   .catch((err) => next(err));

});

module.exports = router;

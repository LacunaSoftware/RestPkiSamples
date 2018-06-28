const express = require('express');
const uuid = require('node-uuid');
const { XmlElementSignatureStarter, StandardSignaturePolicies, XmlSignatureFinisher } = require('restpki-client');

let { Util } = require('../util');

let router = express.Router();
let appRoot = process.cwd();

/*
 * GET /xml-element-signature
 *
 * This route initiates a XML element signature using REST PKI and renders the
 * signature page.
 */
router.get('/', function(req, res, next) {

   // Get an instance of the XmlElementSignatureStarter class, responsible for
   // responsible for receiving the signature elements and the start the
   // signature process.
   let signatureStarter = new XmlElementSignatureStarter(Util.getRestPkiClient());

   // Set the XML to be signed, a sample Brazilian fiscal invoice pre-generated.
   signatureStarter.xmlToSign = Util.getSampleNFePath();

   // Set the ID of the element to be signed.
   signatureStarter.toSignElementId = 'NFe35141214314050000662550010001084271182362300';

   // Set the signature policy.
   signatureStarter.signaturePolicyId = StandardSignaturePolicies.PKI_BRAZIL_NFE_PADRAO_NACIONAL;

   // Set the security context to be used to determine trust in the certificate
   // chain. We have encapsulated the security context choice on util.js.
   signatureStarter.securityContextId = Util.getSecurityContextId();

   // Call the startWithWebPki() method, which initiates the signature. This
   // yields the token, a 43-character case-sensitive URL-safe string, which
   // identifies this signature process. We'll use this value to call the
   // signWithRestPki() method on the WebPKI component
   // (see public/js/signature-form.js) and also to complete the signature after
   // the form is submitted (see post method). This should not be mistaken with
   // with the API access token.
   signatureStarter.startWithWebPki()
   .then((token) => {

      // The token acquired can only be used for a single signature attempt.
      // In order to retry the signature it is necessary to get a new token.
      // This can be a problem if the user uses the back button of the
      // browser, since the browser might show a cached page that we rendered
      // previously, with a now stale token. To prevent this from happening,
      // we set some response headers specifying that the page should not be
      // cached.
      Util.setExpiredPage(res);

      // Render the signature page
      res.render('xml-element-signature', {
         token: token
      });

   })
   .catch((err) => next(err));

});

/*
 * POST /xml-element-signature
 *
 * This route receives the form submission from the view
 * 'xml-element-signature'. We'll call REST PKI to complete the signature.
 */
router.post('/', function(req, res, next) {

   // Retrieve the token from the URL.
   let token = req.body.token;

   // Get an instance of XmlSignatureFinisher class, responsible for complete
   // the signature process.
   let signatureFinisher = new XmlSignatureFinisher(Util.getRestPkiClient());

   // Set the token.
   signatureFinisher.token = token;

   // Call the finish() method, which finalizes the signature process and
   // returns the signed XML.
   signatureFinisher.finish()
   .then((result) => {

      // The "certificate" property of the SignatureResult object contains
      // information about the certificate used by the user to sign the file.
      let signerCert = result.certificate;

      // At this point, you'd typically store the signed PDF on you database.
      // For demonstration purposes, we'll store the PDF on a temporary folder
      // publicly accessible and render a link to it.

      Util.createAppData(); // Make sure the "app-data" folder exists (util.js).
      let filename = uuid.v4() + '.xml';

      // The SignatureResult object has functions for writing the signature file
      // to a local life (writeToFile()) and to get its raw contents
      // (getContent()). For large files, use writeToFile() in order to avoid
      // memory allocation issues.
      result.writeToFile(appRoot + '/public/app-data/' + filename);

      res.render('xml-signature-complete', {
         signedFile: filename,
         signerCert: signerCert
      });

   })
   .catch((err) => next(err));

});

module.exports = router;

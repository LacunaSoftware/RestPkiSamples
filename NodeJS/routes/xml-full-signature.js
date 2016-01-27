var express = require('express');
var request = require('request');
var fs = require('fs');
var uuid = require('node-uuid');
var restPki = require('../lacuna-restpki');
var client = require('../restpki-client');

var router = express.Router();
var appRoot = process.cwd();


/*
 * GET /xml-full-signature
 *
 * This route initiates a full XML signature using REST PKI and renders the signature page.
 */
router.get('/', function(req, res, next) {
	
	var xmlToSignContent = fs.readFileSync(appRoot + '/public/SampleDocument.xml');
	
	// Request to be sent to REST PKI
	var restRequest = {
			
	    // Set the XML to be signed, a sample XML Document
		xml: new Buffer(xmlToSignContent).toString('base64'),
		
		// Signature policy (the ID below corresponds to the PAdES basic policy)
		signaturePolicyId: restPki.standardSignaturePolicies.xadesBes,
		
		// Set a SecurityContext to be used to determine trust in the certificate chain
		securityContextId: restPki.standardSecurityContexts.pkiBrazil,
		// Note: By changing the value above you can accept only certificates from a certain PKI, or
		// from a custom PKI configured on the REST PKI website.

	    // Set the location on which to insert the signature node. If the location is not specified, the signature will appended
	    // to the root element (which is most usual with enveloped signatures).
		signatureElementLocation: {
		    xpath: '//ls:signaturePlaceholder',
		    namespaces: [{ prefix: 'ls', uri: 'http://www.lacunasoftware.com/sample' }],
		    insertionOption: 'appendChild'
		},
		
	};
	
	// Call the action POST Api/PadesSignatures on REST PKI, which initiates the signature. 
	request.post(client.endpoint + 'Api/XmlSignatures/FullXmlSignature', {

		json: true,
		headers: { 'Authorization': 'Bearer ' + client.accessToken },
		body: restRequest
		
	}, function (err, restRes, body) {

	    if (restPki.checkResponse(err, restRes, body, next)) {

	        // This operation yields the token, a 43-character case-sensitive URL-safe string, which identifies this signature process.
	        // We'll use this value to call the signWithRestPki() method on the Web PKI component (see view 'pades-signature') and also
	        // to complete the signature after the form is submitted. This should not be mistaken with the API access token.
	        var token = restRes.body.token;

	        // The token acquired can only be used for a single signature attempt. In order to retry the signature it is
	        // necessary to get a new token. This can be a problem if the user uses the back button of the browser, since the
	        // browser might show a cached page that we rendered previously, with a now stale token. To prevent this from happening,
	        // we set some response headers specifying that the page should not be cached.
	        res.set({
	            'Cache-Control': 'private, no-store, max-age=0, no-cache, must-revalidate, post-check=0, pre-check=0',
	            'Pragma': 'no-cache'
	        });

	        // Render the signature page
	        res.render('xml-full-signature', {
	            token: token,
	            userfile: req.query.userfile
	        });
	    }

	});
});

/*
 * POST /xml-full-signature
 *
 * This route receives the form submission from the view 'xml-full-signature'. We'll call REST PKI to complete the signature.
 */
router.post('/', function(req, res, next) {
	
	// Retrieve the token from the URL
	var token = req.body.token;
	
	// Call the action POST Api/PadesSignatures/{token}/Finalize on REST PKI, which finalizes the signature process and returns the signed PDF
	request.post(client.endpoint + 'Api/XmlSignatures/' + token + '/Finalize', {
		
		json: true,
		headers: { 'Authorization': 'Bearer ' + client.accessToken}
		
	}, function(err, restRes, body) {
		
	    if (restPki.checkResponse(err, restRes, body, next)) {
			
			var signedXmlContent = new Buffer(restRes.body.signedXml, 'base64');

			// At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
			// store the PDF on a temporary folder publicly accessible and render a link to it.
			var filename = uuid.v4() + '.xml';
			var appDataPath = appRoot + '/public/app-data/';
			if (!fs.existsSync(appDataPath)){
				fs.mkdirSync(appDataPath);
			}
			fs.writeFileSync(appDataPath + filename, signedXmlContent);
			res.render('xml-signature-complete', {
				signedFileName: filename,
				signerCert: restRes.body.certificate
			});
			
		}
	});
});

module.exports = router;

var express = require('express');
var request = require('request');
var fs = require('fs');
var uuid = require('node-uuid');
var restPki = require('../lacuna-restpki');
var client = require('../restpki-client');

var router = express.Router();
var appRoot = process.cwd();


/*
 * GET /pades-signature
 *
 * This route initiates a PAdES signature using REST PKI and renders the signature page.
 *
 * Both PAdES signature examples, with a server file and with a file uploaded by the user, use this route. The difference
 * is that, when the file is uploaded by the user, the route is called with a URL argument named "userfile".
 */
router.get('/', function(req, res, next) {
	
	var pdfToSignContent;
	
	// If the user was redirected here by the route "upload" (signature with file uploaded by user), the "userfile" URL
	// argument will contain the filename under the "public/app-data" folder. Otherwise (signature with server file), we'll
	// sign a sample document.
	if (req.query.userfile) {
		pdfToSignContent = fs.readFileSync(appRoot + '/public/app-data/' + req.query.userfile);
	} else {
		pdfToSignContent = fs.readFileSync(appRoot + '/public/SampleDocument.pdf');
	}
	
	// Read the contents of the PDF stamp image
	var pdfStampContent = fs.readFileSync(appRoot + '/resources/PdfStamp.png');
	
	// Request to be sent to REST PKI
	var restRequest = {
			
		// Base64-encoding of the PDF to be signed
		pdfToSign: new Buffer(pdfToSignContent).toString('base64'),
		
		// Signature policy (the ID below corresponds to the PAdES basic policy)
		signaturePolicyId: restPki.standardSignaturePolicies.padesBes,
		
		// Set a SecurityContext to be used to determine trust in the certificate chain
		securityContextId: restPki.standardSecurityContexts.pkiBrazil,
		// Note: By changing the value above you can accept only certificates from a certain PKI, or
		// from a custom PKI configured on the REST PKI website.

		// Set the visual representation for the signature	
		visualRepresentation: {
			
			image: {
				
				// We'll use as background the image previously loaded
				resource: {
					content: new Buffer(pdfStampContent).toString('base64'), // Base64-encoding!
					mimeType: 'image/png'
				},
				
				// (optional) Opacity is an integer from 0 to 100 (0 is completely transparent, 100 is completely opaque). If omitted, 100 is assumed.
				opacity: 50,
				
				// (optional) Specify the image horizontal alignment. Possible values are 'Left', 'Center' and 'Right'. If omitted, 'Center' is assumed.
				horizontalAlign: 'Right',
				
				// (optional) Specify the image vertical alignment. Possible values are 'Top', 'Center' and 'Bottom'. If omitted, 'Center' is assumed.
				verticalAlign: 'Center'
				
			},
			
			text: {
				
				// The tags {{signerName}} and {{signerNationalId}} will be substituted according to the user's certificate
				// signerName -> full name of the signer
				// signerNationalId -> if the certificate is ICP-Brasil, contains the signer's CPF
				text: 'Signed by {{signerName}} ({{signerNationalId}})',
				
				// Specify that the signing time should also be rendered
				includeSigningTime: true,
				
				// Optionally set the horizontal alignment of the text ('Left' or 'Right'), if not set the default is Left
				horizontalAlign: 'Left'
				
			},
			
			position: {
				
				// Page on which to draw the visual representation. Negative values are counted from the end of the document (-1 is last page).
				// Zero means the signature will be placed on a new page appended to the end of the document.
				pageNumber: -1,

				// Measurement units of the values below ('Centimeters' or 'PdfPoints')
				measurementUnits: "Centimeters",
				
				// Automatic placing of signatures within a container, one after the other
				auto: {
					
					// Specification of the container where the signatures will be placed
					container: {
						// Specifying left and right (but no width) results in a variable-width container with the given margins
						left: 1.5,
						right: 1.5,
						// Specifying bottom and height (but no top) results in a bottom-aligned fixed-height container
						bottom: 1.5,
						height: 3
					},
					
					// Specification of the size of each signature rectangle
					signatureRectangleSize: {
						width: 7,
						height: 3
					},
					
					// The signatures will be placed in the container side by side. If there's no room left, the signatures
					// will "wrap" to the next row. The value below specifies the vertical distance between rows
					rowSpacing: 1.5
				}
			}
		}
	};
	
	// Call the action POST Api/PadesSignatures on REST PKI, which initiates the signature. 
	request.post(client.endpoint + 'Api/PadesSignatures', {

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
	        res.render('pades-signature', {
	            token: token,
	            userfile: req.query.userfile
	        });
	    }

	});
});

/*
 * POST /pades-signature
 *
 * This route receives the form submission from the view 'pades-signature'. We'll call REST PKI to complete the signature.
 */
router.post('/', function(req, res, next) {
	
	// Retrieve the token from the URL
	var token = req.body.token;
	
	// Call the action POST Api/PadesSignatures/{token}/Finalize on REST PKI, which finalizes the signature process and returns the signed PDF
	request.post(client.endpoint + 'Api/PadesSignatures/' + token + '/Finalize', {
		
		json: true,
		headers: { 'Authorization': 'Bearer ' + client.accessToken}
		
	}, function(err, restRes, body) {
		
	    if (restPki.checkResponse(err, restRes, body, next)) {
			
			var signedPdfContent = new Buffer(restRes.body.signedPdf, 'base64');

			// At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
			// store the PDF on a temporary folder publicly accessible and render a link to it.
			var filename = uuid.v4() + '.pdf';
			var appDataPath = appRoot + '/public/app-data/';
			if (!fs.existsSync(appDataPath)){
				fs.mkdirSync(appDataPath);
			}
			fs.writeFileSync(appDataPath + filename, signedPdfContent);
			res.render('pades-signature-complete', {
				signedFileName: filename,
				signerCert: restRes.body.certificate
			});
			
		}
	});
});

module.exports = router;

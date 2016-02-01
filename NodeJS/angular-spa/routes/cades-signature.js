var express = require('express');
var request = require('request');
var fs = require('fs');
var uuid = require('node-uuid');
var restPki = require('../lacuna-restpki');
var client = require('../restpki-client');

var router = express.Router();
var appRoot = process.cwd();


/*
 * GET /cades-signature
 *
 * This route initiates a CAdES signature using REST PKI and renders the signature page.
 *
 * Both PAdES signature examples, with a server file and with a file uploaded by the user, use this route. The difference
 * is that, when the file is uploaded by the user, the route is called with a URL argument named "userfile".
 */
router.get('/', function (req, res, next) {

    var contentToSign;

    // If the user was redirected here by the route "upload" (signature with file uploaded by user), the "userfile" URL
    // argument will contain the filename under the "public/app-data" folder. Otherwise (signature with server file), we'll
    // sign a sample document.
    if (req.query.userfile) {
        contentToSign = fs.readFileSync(appRoot + '/public/app-data/' + req.query.userfile);
    } else {
        contentToSign = fs.readFileSync(appRoot + '/public/SampleDocument.pdf');
    }

    // Request to be sent to REST PKI
    var restRequest = {

        // Base64-encoding of the content to be signed
        contentToSign: new Buffer(contentToSign).toString('base64'),

        // Optionally, set whether the content should be encapsulated in the resulting CMS. If this parameter is ommitted,
        // the following rules apply:
        // - If no CmsToSign is given, the resulting CMS will include the content
        // - If a CmsToCoSign is given, the resulting CMS will include the content if and only if the CmsToCoSign also includes the content
        encapsulateContent: true,

        // Set the signature policy
        signaturePolicyId: restPki.standardSignaturePolicies.pkiBrazilCadesAdrBasica,

        // Optionally, set a SecurityContext to be used to determine trust in the certificate chain
        // securityContextId: restPki.standardSecurityContexts.pkiBrazil,
        // Note: Depending on the signature policy chosen above, setting the security context may be mandatory (this is not
        // the case for ICP-Brasil policies, which will automatically use the PkiBrazil security context if none is passed)
    };

    // Call the action POST Api/PadesSignatures on REST PKI, which initiates the signature. 
    request.post(client.endpoint + 'Api/CadesSignatures', {

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
            res.render('cades-signature', {
                token: token,
                userfile: req.query.userfile
            });
        }

    });
});

/*
 * POST /cades-signature
 *
 * This route receives the form submission from the view 'cades-signature'. We'll call REST PKI to complete the signature.
 */
router.post('/', function (req, res, next) {

    // Retrieve the token from the URL
    var token = req.body.token;

    // Call the action POST Api/PadesSignatures/{token}/Finalize on REST PKI, which finalizes the signature process and returns the signed PDF
    request.post(client.endpoint + 'Api/CadesSignatures/' + token + '/Finalize', {

        json: true,
        headers: { 'Authorization': 'Bearer ' + client.accessToken }

    }, function (err, restRes, body) {

        if (restPki.checkResponse(err, restRes, body, next)) {

            var signedContent = new Buffer(restRes.body.cms, 'base64');

            // At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
            // store the PDF on a temporary folder publicly accessible and render a link to it.
            var filename = uuid.v4() + '.p7s';
            var appDataPath = appRoot + '/public/app-data/';
            if (!fs.existsSync(appDataPath)) {
                fs.mkdirSync(appDataPath);
            }
            fs.writeFileSync(appDataPath + filename, signedContent);
            res.render('cades-signature-complete', {
                signedFileName: filename,
                signerCert: restRes.body.certificate
            });

        }
    });
});

module.exports = router;

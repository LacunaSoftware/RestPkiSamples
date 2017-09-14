var express = require('express');
var request = require('request');
var fs = require('fs');
var uuid = require('node-uuid');
var restPki = require('../lacuna-restpki');
var client = require('../restpki-client');

var router = express.Router();
var appRoot = process.cwd();


/*
 * GET /batch-xml-signature
 *
 * This route only does the renderization of the elements' ids of the XML file (EventoManifesto.xml) on the page, which
 * will be signed. The page will handle each element id one by one and will call the server synchronously to start and
 * complete each signature. It has to be synchronous, because a signature needs the xml document's content, containing
 * the previous signature.
 */
router.get('/', function (req, res, next) {

    // It is up to your application's business logic to determine which element ids will compose the batch.
    var elementsIds = [];
    for (var i = 1; i <= 10; i++) {
        elementsIds.push('ID2102100000000000000000000000000000000000008916' + (i < 10 ? '0' + i : i));
    }

    // Render the signature page
    res.render('batch-xml-signature', {
        elementsIds: elementsIds
    });
});

/*
 * POST /batch-xml-signature/start
 *
 * This route initiates a XML element signature using REST PKI and return the token, which identifies the signature's
 * process.
 */
router.post('/start', function (req, res, next) {

    // Retrieve the token from the URL
    var elementId = req.body.elemId;

    // Logic to use a single file for the batch signature. Acts together with "complete" route.
    var xmlPath = appRoot + '/public/EventoManifesto.xml';
    if (req.body.fileId) {
        xmlPath = appRoot + '/public/app-data/' + req.body.fileId;
    }
    var xmlToSignContent = fs.readFileSync(xmlPath);

    // Request to be sent to REST PKI
    var restRequest = {

        // Set the xml to be signed content in Base64-encoding
        xml: new Buffer(xmlToSignContent).toString('base64'),

        // Set the ID of the element to be signed
        elementToSignId: elementId,

        // Signature policy
        signaturePolicyId: restPki.standardSignaturePolicies.pkiBrazilNFePadraoNacional,

        // Optionally, set a SecurityContext to be used to determine trust in the certificate chain. Since we're using
        // the StandardXmlSignaturePolicies.PkiBrazil.NFePadraoNacional policy, the security context will default to
        // PKI Brazil (ICP-Brasil)
        // securityContextId: restPki.standardSecurityContexts.pkiBrazil
        // Note: By changing the SecurityContext above you can accept only certificates from a certain PKI
        // Set a SecurityContext to be used to determine trust in the certificate chain
    };

    // Call the action POST Api/PadesSignatures on REST PKI, which initiates the signature.
    request.post(client.endpoint + 'Api/XmlSignatures/XmlElementSignature', {

        json: true,
        headers: { 'Authorization': 'Bearer ' + client.accessToken },
        body: restRequest

    }, function (err, restRes, body) {

        if (restPki.checkResponse(err, restRes, body, next)) {

            // This operation yields the token, a 43-character case-sensitive URL-safe string, which identifies this
            // signature process. We'll use this value to call the signWithRestPki() method on the Web PKI component
            // (see view 'pades-signature') and also to complete the signature after the form is submitted. This should
            // not be mistaken with the API access token.
            var token = restRes.body.token;

            // Return a JSON with the token obtained form REST PKI (the page will use jQuery to decode this value)
            res.json(token);
        }

    });
});

/*
 * POST /batch-xml-signature/complete
 *
 * This route completes the signature using REST PKI and return the filename, containing the signed file. This file may
 * be used on the next signature or, if there's not next signatures, the link to download the result file will be shown.
 */
router.post('/complete', function (req, res, next) {

    // Retrieve the token from the URL
    var token = req.body.token;

    // Logic to use a single file for the batch signature. Acts together with "start" route.
    var filename = uuid.v4() + '.xml';
    if (req.body.fileId) {
        filename = req.body.fileId;
    }

    // Call the action POST Api/XmlSignatures/{token}/Finalize on REST PKI, which finalizes the signature process and
    // returns the signed XML.
    request.post(client.endpoint + 'Api/XmlSignatures/' + token + '/Finalize', {

        json: true,
        headers: { 'Authorization': 'Bearer ' + client.accessToken }

    }, function (err, restRes, body) {

        if (restPki.checkResponse(err, restRes, body, next)) {

            // Receive the signed XML's content as response of the REST PKI action to finalize a signature process
            var signedXmlContent = new Buffer(restRes.body.signedXml, 'base64');

            // At this point, you'd typically store the signed XML on your database. For demonstration purposes, we'll
            // store the PDF on a temporary folder publicly accessible and render a link to it.
            var appDataPath = appRoot + '/public/app-data/';
            if (!fs.existsSync(appDataPath)) {
                fs.mkdirSync(appDataPath);
            }
            fs.writeFileSync(appDataPath + filename, signedXmlContent);
            res.json(filename);
        }
    });
});

module.exports = router;

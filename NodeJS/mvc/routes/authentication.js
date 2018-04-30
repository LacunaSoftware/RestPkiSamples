var express = require('express');
var request = require('request');
var restPki = require('../lacuna-restpki');
var util = require('../util');

var router = express.Router();

/*
 * GET /authentication
 *
 * This route initiates an certificate authentication using REST PKI and renders
 * the auth page.
 */
router.get('/', function(req, res, next) {

   // Request to be sent to REST PKI.
   var restRequest = {
      // Set a SecurityContext to be used to determine trust in the certificate
      // chain for authentication.
      securityContextId: restPki.standardSecurityContexts.pkiBrazil
   };

   // Call the action POST Api/Authentications on REST PKI, which initiates the
   // authentication.
   request.post(util.endpoint + 'Api/Authentications', {

      json: true,
      headers: {'Authorization': 'Bearer ' + util.accessToken},
      body: restRequest

   }, function(err, restRes, body) {

      if (restPki.checkResponse(err, restRes, body, next)) {

         // This operation yields the token, a 43-character case-sensitive
         // URL-safe string, which identifies this signature process. We'll use
         // this value to call the signWithRestPki() method on the Web PKI
         // component (see view 'pades-signature') and also to complete the
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
         res.render('authentication', {
            token: token
         });
      }

   });
});

/*
 * POST /authentication
 *
 * This route receives the form submission from the view 'authentication'. We'll
 * call REST PKI to complete the authentication.
 */
router.post('/', function(req, res, next) {

   // Retrieve the token from the URL.
   var token = req.body.token;

   // Call the action POST Api/Authentications/{token}/Finalize on REST PKI,
   // which finalizes the authentication process and returns the certificate
   // validation results.
   request.post(util.endpoint + 'Api/Authentications/' + token + '/Finalize', {

      json: true,
      headers: {'Authorization': 'Bearer ' + util.accessToken}

   }, function(err, restRes, body) {

      if (restPki.checkResponse(err, restRes, body, next)) {

         res.render('authentication-complete', {
            certificate: restRes.body.certificate,
            validationResults: new restPki.ValidationResults(restRes.body.validationResults),
         });

      }

   });
});

module.exports = router;

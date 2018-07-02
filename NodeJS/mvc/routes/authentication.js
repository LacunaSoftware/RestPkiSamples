const express = require('express');
const { Authentication } = require('restpki-client');

const { Util } = require('../util');

let router = express.Router();


/*
 * GET /authentication
 *
 * This route initiates a certificate authentication using REST PKI and renders
 * the authentication page.
 */
router.get('/', function(req, res, next) {

   // Get an instance of the Authentication class.
   let auth = new Authentication(Util.getRestPkiClient());

   // Call the startWithWebPki() method, which initiates the authentication.
   // This yields the "token", a 22-character case-sensitive URL-safe string,
   // which represents this authentication process. We'll use this value to call
   // the signWithRestPki() method on the Web PKI component
   // (see public/javascripts/signature-form.js) and also call the
   // completeWithWebPki() method on "complete" step. This should not be
   // mistaken with the API access token. We have encapsulated the security
   // context choice on util.js.
   auth.startWithWebPki(Util.getSecurityContextId(res.locals.environment))
   .then((token) => {

      // The token acquired can only be used for a single authentication.
      // In order to retry authenticating it is necessary to get a new token.
      // This can be a problem if the user uses the back button of the
      // browser, since the browser might show a cached page that we rendered
      // previously, with a now stale token. To prevent this from happening,
      // we call the function setExpiredPage(), located in util.js, which sets
      // HTTP headers to prevent caching of the page.
      Util.setExpiredPage(res);

      // Render the authentication page.
      res.render('authentication', {
         token: token
      });

   })
   .catch((err) => next(err));

});

/*
 * POST /authentication
 *
 * This route receives the form submission from the view 'authentication'. We'll
 * call REST PKI to complete the authentication.
 */
router.post('/', function(req, res, next) {

   // Get an instance of the Authentication class (util.js).
   let auth = new Authentication(Util.getRestPkiClient());

   // Call the completeWithWebPki() method with the token, which finalizes the
   // authentication process. The call yields a ValidationResults which denotes
   // whether the authentication was success or not.
   auth.completeWithWebPki(req.body.token)
   .then((result) => {

      // Check the authentication result.
      if (!result.validationResults.isValid()) {
         // If the authentication was not successful, we render a page showing
         // what went wrong.
         res.render('authentication-fail', {
            validationResults: result.validationResults
         });
         return;
      }

      // At this point, you have assurance tha the certificate is valid
      // according to the TrustArbitrator you selected when starting the
      // authentication and that the user is indeed the certificate's subject.
      // Now, you'd typically query your database for a user that matches one of
      // the certificate's fields, such as userCert.emailAddress or
      // userCert.pkiBrazil.cpf (the actual field to be used as key depends on
      // your application's business logic) and set the user ID on the cookie
      // as if it were the user ID.
      let userCert = result.certificate;
      req.session.userId = userCert.emailAddress;

      // Redirect to the initial page with the user logged in.
      res.redirect('/');

   })
   .catch((err) => next(err));

});

module.exports = router;

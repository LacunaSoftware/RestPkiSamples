using Lacuna.RestPki.Api;
using Lacuna.RestPki.SampleSite.Models;
using SampleSite.Classes;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.Mvc;
using System.Web.Security;

namespace Lacuna.RestPki.SampleSite.Controllers {

	public class AuthenticationController : BaseController {

		/*
		 * This action initiates an authentication with REST PKI and renders the authentication page.
		 */
		[HttpGet]
		public ActionResult Index() {

			// Get an instance of the Authentication class
			var auth = Util.GetRestPkiClient().GetAuthentication();

			// Call the StartWithWebPki() method, which initiates the authentication. This yields the "token", a 22-character
			// case-sensitive URL-safe string, which represents this authentication process. We'll use this value to call the
			// signWithRestPki() method on the Web PKI component (see javascript on the view) and also to call the
			// CompleteWithWebPki() method on the POST action below (this should not be mistaken with the API access token).
			var token = auth.StartWithWebPki(StandardSecurityContexts.PkiBrazil);

			// Note: By changing the SecurityContext above you can accept only certificates from a certain PKI,
			// for instance, ICP-Brasil (Lacuna.RestPki.Api.StandardSecurityContexts.PkiBrazil).

			// The token acquired above can only be used for a single authentication. In order to retry authenticating it is
			// necessary to get a new token. This can be a problem if the user uses the back button of the browser, since the
			// browser might show a cached page that we rendered previously, with a now stale token. To prevent this from happening,
			// we call the method SetExpiredPage(), located in BaseController.cs, which sets HTTP headers to prevent caching of the page.
			base.SetExpiredPage();

			// Render the authentication page with the token obtained from REST PKI
			return View(new AuthenticationModel() {
				Token = token
			});
		}

		/*
		 * This action receives the form submission from the view. We'll call REST PKI to validate the authentication.
		 */
		[HttpPost]
		public ActionResult Index(AuthenticationModel model) {

			// Get an instance of the Authentication class
			var auth = Util.GetRestPkiClient().GetAuthentication();

			// Call the CompleteWithWebPki() method with the token, which finalizes the authentication process. The call yields a
			// ValidationResults which denotes whether the authentication was successful or not.
			var validationResults = auth.CompleteWithWebPki(model.Token);

			// Check the authentication result
			if (!validationResults.IsValid) {
				// If the authentication was not successful, we render a page showing what went wrong
				return View("Failed", validationResults);
			}

			// At this point, you have assurance that the certificate is valid according to the TrustArbitrator you
			// selected when starting the authentication and that the user is indeed the certificate's subject. Now,
			// you'd typically query your database for a user that matches one of the certificate's fields, such as
			// userCert.EmailAddress or userCert.PkiBrazil.CPF (the actual field to be used as key depends on your
			// application's business logic) and set the user ID on the auth cookie. For demonstration purposes,
			// we'll set the email address directly on the cookie as if it were the user ID.
			var userCert = auth.GetCertificate();
			FormsAuthentication.SetAuthCookie(userCert.EmailAddress, false);

			// Redirect to the initial page with the user logged in
			return RedirectToAction("Index", "Home");
		}


	}
}

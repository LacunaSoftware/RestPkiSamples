using Lacuna.RestPki.SampleSite.Models;
using SampleSite.Classes;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using System.Web;
using System.Web.Mvc;
using System.Web.Security;

namespace Lacuna.RestPki.SampleSite.Controllers {

	public class AuthenticationWithoutDirectCommunicationController : BaseController {

		// GET: AuthenticationWithoutDirectCommunication
		[HttpGet]
		public async Task<ActionResult> Index() {

			// Get an instance of the Authentication class.
			var auth = Util.GetRestPkiClient().GetAuthentication();

			// Call the Start() method, which initiates a authentication. This yields the "nonce" to be signed.
			// We'll call use this value to call the signHash() method on the Web PKI component (see javascript
			// on the view) and also to call the Complete() method on the POST action below.
			var nonce = await auth.StartAsync();

			// The nonce acquired above can only be used for a single authentication. In order to retry
			// authenticating it is necessary to get a new token. This can be a problem if the user uses the
			// back button of the browser, since the browser might show a cached page that we rendered
			// previously, with a now stale token. To prevent this from happening, we call the method
			// SetNoCacheHeaders() (in BaseController) which sets HTTP headers to prevent caching of the
			// page.
			base.SetNoCacheHeaders();

			// Render the authentication page with the nonce obtained from REST PKI.
			return View(new AuthModel() {
				Nonce = nonce
			});
		}

		// POST: AuthenticationWithoutDirectCommunication
		[HttpPost]
		public async Task<ActionResult> Index(AuthModel model) {

			// Get an instance of the Authentication class.
			var auth = Util.GetRestPkiClient().GetAuthentication();

			// Call the Complete method with the signed nonce, the signature value, the signer, the signer
			// certificate's content and the security context. The call yields a Validation Results which
			// denotes whether the authentication was successful or not.
			var validationResults = await auth.CompleteAsync(model.Nonce, model.CertContent, model.Signature, Util.GetSecurityContextId());

			// Check the authentication result.
			if (!validationResults.IsValid) {
				// If the authentication was not successful, we render a page showing what went wrong.
				return View("Failed", validationResults);
			}

			// At this point, you have assurance that the certificate is valid according to the
			// TrustArbitrator you selected when starting the authentication and that the user is indeed the
			// certificate's subject. Now, you'd typically query your database for a user that matches one of
			// the certificate's fields, such as userCert.EmailAddress or userCert.PkiBrazil.CPF (the actual
			// field to be used as key depends on your application's business logic) and set the user ID on
			// the auth cookie. For demonstration purposes, we'll set the email address directly on the
			// cookie as if it were the user ID.
			var userCert = auth.GetCertificate();
			FormsAuthentication.SetAuthCookie(userCert.EmailAddress, false);

			// Redirect to the initial page with the user logged in.
			return RedirectToAction("Index", "Home");
		}
	}
}
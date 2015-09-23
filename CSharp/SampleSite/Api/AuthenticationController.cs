using Lacuna.RestPki.Api;
using Lacuna.RestPki.Client;
using SampleSite.Classes;
using SampleSite.Models;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Net;
using System.Net.Http;
using System.Threading.Tasks;
using System.Web;
using System.Web.Http;
using System.Web.Security;

namespace SampleSite.Api {

	/**
	 * This controller contains the server-side logic for the authentication example. The client-side is implemented at:
	 * - HTML: Views/Home/Authentication.cshtml
	 * - JS: Content/js/app/authentication.js
	 */
	public class AuthenticationController : ApiController {

		/**
		 * GET Api/Authentication
		 * 
		 *	This action is called once the user clicks the "Sign In" button.
		 */
		[HttpGet, Route("api/Authentication")]
		public async Task<IHttpActionResult> Get() {

			// Get an instance of the Authentication class
			var auth = Util.GetRestPkiClient().GetAuthentication();

			// Call the startWithWebPki() method, which initiates the authentication. This yields the token,
			// a 22-character case-sensitive URL-safe string, which we'll send to the page in order to pass on the
			// signWithRestPki method of the Web PKI component.
			var token = await auth.StartWithWebPkiAsync(Util.SecurityContextId);

			// Note: By changing the SecurityContext above you can accept only certificates from a certain PKI,
			// for instance, ICP-Brasil (Lacuna.RestPki.Api.StandardSecurityContexts.PkiBrazil).

			// Return the token to the page
			return Ok(token);
		}

		/**
		 * POST Api/Authentication?token=xxx
		 * 
		 * This action is called after signing the nonce on the client-side with the user's certificate. We'll once
		 * again use the Authentication class to do the actual work.
		 */
		[HttpPost, Route("api/Authentication/{token}")]
		public async Task<IHttpActionResult> Post(string token) {

			// Get an instance of the Authentication class
			var auth = Util.GetRestPkiClient().GetAuthentication();

			// Call the completeWithWebPki() method, which finalizes the authentication process. It receives as input
			// only the token that was yielded previously (which we sent to the page and the page sent us back on the URL).
			// The call yields a ValidationResults which denotes whether the authentication was successful or not
			var validationResults = await auth.CompleteWithWebPkiAsync(token);

			// Check the authentication result
			if (!validationResults.IsValid) {
				// The authentication failed, inform the page
				return Ok(new AuthenticationPostResponse() {
					Success = false,
					Message = "Authentication failed",
					ValidationResults = validationResults.ToString()
				});
			}

			var userCert = auth.GetCertificate();

			// At this point, you have assurance that the certificate is valid according to the
			// TrustArbitrator you selected above and that the user is indeed the certificate's
			// subject. Now, you'd typically query your database for a user that matches one of the
			// certificate's fields, such as userCert.EmailAddress or userCert.PkiBrazil.CPF (the actual field
			// to be used as key depends on your application's business logic) and set the user
			// ID on the auth cookie. For demonstration purposes, we'll set the email address directly
			// on the cookie as if it were the user ID.
			FormsAuthentication.SetAuthCookie(userCert.EmailAddress, false);

			// Inform the page of the success
			return Ok(new AuthenticationPostResponse() {
				Success = true
			});
		}
	}
}

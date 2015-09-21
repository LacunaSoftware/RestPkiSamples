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
	 * 
	 * This controller uses the PKCertificateAuthentication class to implement the authentication. For more information, see:
	 * http://pki.lacunasoftware.com/Help/html/c7e43b5d-f745-43a7-92dc-74e777c1caa0.htm
	 */
	public class AuthenticationController : ApiController {

      /**
		 * GET Api/Authentication
		 * 
		 *	This action is called once the user clicks the "Sign In" button. It uses the PKCertificateAuthentication
		 *	class to generate and store a cryptographic nonce, which will then be sent to the page for signature using
		 *	the user's certificate.
		 */
      [HttpGet, Route("api/Authentication")]
      public async Task<IHttpActionResult> Get() {
			var auth = Util.GetRestPkiClient().GetAuthentication();
         var token = await auth.StartWithWebPkiAsync(Util.SecurityContextId);
			return Ok(token);
		}

      /**
		 * POST Api/Authentication
		 * 
		 * This action is called after signing the nonce on the client-side with the user's certificate. We'll once
		 * again use the PKCertificateAuthentication class to do the actual work.
		 */
      [HttpPost, Route("api/Authentication/{token}")]
      public async Task<IHttpActionResult> Post(string token) {

			var auth = Util.GetRestPkiClient().GetAuthentication();
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
			// certificate's fields, such as cert.EmailAddress or cert.PkiBrazil.CPF (the actual field
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

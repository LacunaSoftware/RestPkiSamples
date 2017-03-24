using CoreWebApp.Classes;
using CoreWebApp.Models;
using Lacuna.RestPki.Client;
using Microsoft.AspNetCore.Mvc;
using Microsoft.Extensions.Options;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;

namespace CoreWebApp.Controllers {

	[Route("api/[controller]")]
	public class AuthenticationController : Controller {

		private RestPkiConfig restPkiConfig;

		public AuthenticationController(IOptions<RestPkiConfig> optionsAccessor) {
			this.restPkiConfig = optionsAccessor.Value;
		}

		// GET api/authentication
		[HttpGet]
		public async Task<string> Get() {

			var client = Util.GetRestPkiClient(restPkiConfig);

			// Get an instance of the Authentication class
			var auth = new Authentication(client);

			// Call the StartWithWebPkiAsync() method, which initiates the authentication. This yields the "token", a 22-character
			// case-sensitive URL-safe string, which represents this authentication process. We'll use this value to call the
			// signWithRestPki() method on the Web PKI component (see javascript on the angular controller) and also to call the
			// CompleteWithWebPkiAsync() method on the POST action below (this should not be mistaken with the API access token).
			var token = await auth.StartWithWebPkiAsync(Lacuna.RestPki.Api.StandardSecurityContexts.PkiBrazil);

			// Alternative option: authenticate the user with a custom security context containting, for instance, your private PKI certificate
			//var token = await auth.StartWithWebPkiAsync(new Guid("ID OF YOUR CUSTOM SECURITY CONTEXT"));

			// For instance, to use the test certificates on Lacuna Test PKI (for development purposes only!):
			//var token = await auth.StartWithWebPkiAsync(new Guid("803517ad-3bbc-4169-b085-60053a8f6dbf"));

			return token;
		}

		// POST api/authentication/{token}
		[HttpPost("{token}")]
		public async Task<IActionResult> Post(string token) {

			var client = Util.GetRestPkiClient(restPkiConfig);

			// Get an instance of the Authentication class
			var auth = new Authentication(client);

			// Call the CompleteWithWebPki() method with the token, which finalizes the authentication process. The call yields a
			// ValidationResults which denotes whether the authentication was successful or not.
			var vr = await auth.CompleteWithWebPkiAsync(token);
			var userCert = auth.GetCertificate();

			// Check the authentication result
			if (!vr.IsValid) {
				return BadRequest(new ValidationErrorModel(vr));
			}

			// At this point, you have assurance that the certificate is valid according to the TrustArbitrator you
			// selected when starting the authentication and that the user is indeed the certificate's subject. Now,
			// you'd typically query your database for a user that matches one of the certificate's fields, such as
			// userCert.EmailAddress or userCert.PkiBrazil.CPF (the actual field to be used as key depends on your
			// application's business logic) and set the user ID on the authentication framework your app uses.
			// For demonstration purposes, we'll just show some of the user's certificate information.

			var response = new AuthenticationPostResponse() {
				Certificate = new CertificateModel(userCert)
			};

			return Ok(response);
		}
	}
}

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
			var auth = new Authentication(client);
			var token = await auth.StartWithWebPkiAsync(Lacuna.RestPki.Api.StandardSecurityContexts.PkiBrazil);
			return token;
		}

		// POST api/authentication/{token}
		[HttpPost("{token}")]
		public async Task<IActionResult> Post(string token) {

			var client = Util.GetRestPkiClient(restPkiConfig);
			var auth = new Authentication(client);
			var vr = await auth.CompleteWithWebPkiAsync(token);
			var userCert = auth.GetCertificate();

			// Check the authentication result
			if (!vr.IsValid) {
				return BadRequest(new ValidationErrorModel(vr));
			}

			var response = new AuthenticationPostResponse() {
				Certificate = new CertificateModel(userCert)
			};

			return Ok(response);
		}
	}
}

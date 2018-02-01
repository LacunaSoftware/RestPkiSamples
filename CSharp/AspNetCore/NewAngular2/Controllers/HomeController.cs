using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using Microsoft.AspNetCore.Mvc;
using Microsoft.Extensions.Options;
using NewAngular2.Classes;
using NewAngular2.Models;

namespace NewAngular2.Controllers {

	public class HomeController : Controller {

		private RestPkiConfig restPkiConfig;

		public HomeController(IOptions<RestPkiConfig> optionsAccessor) {
			this.restPkiConfig = optionsAccessor.Value;
		}

		public IActionResult Index() {

			// This code is here only to check if the API access token was set, in order to show a nice message to help new developers.
			// You are encouraged to comment it.
			if (string.IsNullOrEmpty(restPkiConfig.AccessToken) || restPkiConfig.AccessToken.Contains(" API ")) {
				throw new Exception("The API access token was not set! Hint: to run this sample you must generate an API access token on the REST PKI website and paste it on the appsettings.json file");
			}

			return View(new SpaModel() {
				RestPkiConfig = restPkiConfig
			});
		}
	}
}

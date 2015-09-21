using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.Mvc;
using System.Web.Security;

namespace SampleSite.Controllers {
	
	public class HomeController : Controller {

		// Renders the main page
		public ActionResult Index() {
			return View();
		}

		// Renders the authentication page (actual work for authenticating the user is in the AuthenticationController, on the Api folder)
		public ActionResult Authentication() {
			// If the user is already signed in, let's sign him out
			if (User.Identity.IsAuthenticated) {
				FormsAuthentication.SignOut();
				return RedirectToAction("Authentication");
			}
			return View();
		}

		// Signs the user out
		public ActionResult SignOut() {
			FormsAuthentication.SignOut();
			return RedirectToAction("Index");
		}

		// Renders the PAdES signature page (actual work for performing the signature is in the PadesSignatureController, on the Api folder)
		public ActionResult PadesSignature() {
			return View();
		}
	}
}
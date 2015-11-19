using SampleSite.Classes;
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

		// Signs the user out
		public ActionResult SignOut() {
			FormsAuthentication.SignOut();
			return RedirectToAction("Index");
		}

	}
}
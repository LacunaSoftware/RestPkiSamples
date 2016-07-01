using Lacuna.RestPki.Api;
using Lacuna.RestPki.Client;
using SampleSite.Classes;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.Mvc;

namespace Lacuna.RestPki.SampleSite.Controllers {

	public class OpenPadesSignatureController : Controller {

		[HttpGet]
		public ActionResult Index(string userfile) {
			if (string.IsNullOrEmpty(userfile)) {
				return HttpNotFound();
			}
			var filename = userfile.Replace("_", "."); // Note: we're passing the filename argument with "." as "_" because of limitations of ASP.NET MVC
			var sigExplorer = new PadesSignatureExplorer(Util.GetRestPkiClient()) {
				Validate = true,
				ImplicitPolicy = StandardPadesSignaturePolicies.Basic,
				SecurityContext = StandardSecurityContexts.PkiBrazil
			};
			sigExplorer.SetSignatureFile(Server.MapPath("~/App_Data/" + filename));
			var signature = sigExplorer.Open();
			return View(signature);
		}
	}
}

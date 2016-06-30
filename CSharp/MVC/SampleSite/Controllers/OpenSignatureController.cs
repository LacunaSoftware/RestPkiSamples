using Lacuna.RestPki.Api;
using SampleSite.Classes;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.Mvc;

namespace Lacuna.RestPki.SampleSite.Controllers {

	public class OpenSignatureController : Controller {

		[HttpGet]
		public ActionResult Index(string userfile) {

			var filename = userfile.Replace("_", "."); // Note: we're passing the filename argument with "." as "_" because of limitations of ASP.NET MVC
			var sigExplorer = Util.GetRestPkiClient().GetCadesSignatureExplorer();
			sigExplorer.SetSignatureFile(Server.MapPath("~/App_Data/" + filename));
			sigExplorer.ExplicitValidationPolicies.Add(StandardCadesSignaturePolicies.PkiBrazil.AdrBasica);
			sigExplorer.ImplicitValidationPolicy = StandardCadesSignaturePolicies.CadesBes;
			sigExplorer.ValidationSecurityContext = StandardSecurityContexts.PkiBrazil;
			sigExplorer.Validate = true;
			sigExplorer.SetDataFile(@"C:\temp\C6FD288E7B4B0B1D58AEA06672706958.pdf");
			var signature = sigExplorer.Open();
			return null;
		}

	}
}
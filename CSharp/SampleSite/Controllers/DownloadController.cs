using SampleSite.Classes;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Net;
using System.Web;
using System.Web.Hosting;
using System.Web.Mvc;

namespace SampleSite.Controllers {

	/**
	 * This controller's purpose is to download the sample file that is signed during the
	 * signature examples and download a previously performed signature. The actual work for
	 * performing signatures is done in the controllers on the Api folder (CadesSignatureController
	 * and PadesSignatureController).
	 * 
	 * These actions could be on a ApiController, but, since they return files, they are simpler
	 * to implement in a MVC controller.
	 */
	public class DownloadController : Controller {

		// GET Download/File/{id}
		[HttpGet]
		public ActionResult File(string id) {
			if (string.IsNullOrEmpty(id)) {
				return HttpNotFound();
			}
			var filename = id.Replace("_", "."); // Note: we're passing the filename argument with "." as "_" because of limitations of ASP.NET MVC
			var path = HostingEnvironment.MapPath(string.Format("~/App_Data/{0}", filename));
			if (!System.IO.File.Exists(path)) {
				return HttpNotFound();
			}
			var content = System.IO.File.ReadAllBytes(path);
			return File(content, MimeMapping.GetMimeMapping(filename), filename);
		}

		// GET Download/Sample
		[HttpGet]
		public ActionResult Sample() {
			var fileContent = Util.GetSampleDocContent();
			return File(fileContent, "application/pdf", "Sample.pdf");
		}
	}
}

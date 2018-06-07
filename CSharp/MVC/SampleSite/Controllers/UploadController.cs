using Lacuna.RestPki.SampleSite.Models;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Web;
using System.Web.Mvc;

namespace Lacuna.RestPki.SampleSite.Controllers {

	/*
	 * This controller allows the user to upload a file to be signed. Once the file is uploaded, we save it
	 * to the App_Data folder and redirect the user to Index action on either CadesController or
	 * PadesController passing the filename on the "userfile" URL argument.
	 */
	public class UploadController : Controller {

		[HttpGet]
		public ActionResult Index(string rc) {
			return View(new UploadModel() {
				ReturnController = rc
			});
		}

		[HttpPost]
		public ActionResult Index(UploadModel model) {

			// Check that a file was indeed uploaded.
			if (Request.Files.Count == 0) {
				throw new Exception("No files uploaded");
			}

			var file = Request.Files[0];
			var extension = new FileInfo(file.FileName).Extension;

			// Create the App_Data folder if it does not exist.
			var appDataPath = Server.MapPath("~/App_Data");
			if (!Directory.Exists(appDataPath)) {
				Directory.CreateDirectory(appDataPath);
			}

			// Generate a unique id.
			var id = Guid.NewGuid();

			// Combine the unique id and the original extension.
			var filename = string.Format("{0}{1}", id, extension);

			// Save the file to the App_Data folder with the unique filename.
			file.SaveAs(Path.Combine(appDataPath, filename));

            // Redirect the user to the Index action on either CadesController or PadesController, passing
            // the name of the file as a URL argument.
			return RedirectToAction("Index", model.ReturnController, new { userfile = filename.Replace(".", "_") });
		}
	}
}

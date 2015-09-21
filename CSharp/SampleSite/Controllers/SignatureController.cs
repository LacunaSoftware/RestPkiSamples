using SampleSite.Classes;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Net;
using System.Web;
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
	public class SignatureController : Controller {

		// GET Signature/SampleDocument
		[HttpGet]
		public ActionResult SampleDocument() {
			var fileContent = Util.GetSampleDocContent();
			return File(fileContent, "application/pdf", "SampleDocument.pdf");
		}

		// GET Signature/Download/{id}
		[HttpGet]
		public ActionResult Download(Guid id) {
			using (var dbContext = new DbContext()) {
				var signature = dbContext.Signatures.FirstOrDefault(s => s.Id == id);
				if (signature == null) {
					return HttpNotFound();
				}
				if (signature.Type == SignatureTypes.Cades) {
					return File(signature.Content, "application/pkcs7-signature", String.Format("{0}.p7s", id));
				} else {
					return File(signature.Content, "application/pdf", String.Format("{0}.pdf", id));
				}
			}
		}
	}
}
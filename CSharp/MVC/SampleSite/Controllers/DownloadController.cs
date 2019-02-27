using Lacuna.RestPki.SampleSite.Classes;
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
	 * signature examples or download a upload file for signature or download a previously performed
	 * signature.
	 */
	public class DownloadController : Controller {

		// GET Download/File/{id}
		[HttpGet]
		public ActionResult File(string id) {
            byte[] content;

            if (id == null) {
                return HttpNotFound();
            }

            string extension;
            try {
                content = StorageMock.Read(id, out extension);
            } catch (FileNotFoundException) {
                return HttpNotFound();
            }

            var filename = id + extension;
			return File(content, MimeMapping.GetMimeMapping(filename), filename);
		}

		// GET Download/Sample
		[HttpGet]
		public ActionResult Sample() {
			var fileContent = StorageMock.Read(Util.GetSampleDocPath());
			return File(fileContent, "application/pdf", "Sample.pdf");
		}

		// GET Download/Doc/{id}
		[HttpGet]
		public ActionResult Doc(int id) {
			var fileContent = StorageMock.Read(Util.GetBatchDocPath(id));
			return File(fileContent, "application/pdf", string.Format("Doc{0:D2}.pdf", id));
		}

		// GET Download/Sample
		[HttpGet]
		public ActionResult SampleNFe() {
			var fileContent = StorageMock.Read(Util.GetSampleNFePath());
			return File(fileContent, "text/xml", "SampleNFe.xml");
		}

		// GET Download/SampleInvoice
		[HttpGet]
		public ActionResult SampleInvoice() {
			var fileContent = StorageMock.Read(Util.GetXmlInvoiceWithSigsPath());
			return File(fileContent, "text/xml", "InvoiceWithSigs.xml");
		}

		// GET Download/SamplePeer
		[HttpGet]
		public ActionResult SamplePeer() {
			var fileContent = StorageMock.Read(Util.GetSamplePeerDocumentPath());
			return File(fileContent, "text/xml", "SamplePeerDocument.xml");
		}
	}
}

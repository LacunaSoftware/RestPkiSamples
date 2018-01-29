using Lacuna.RestPki.Api;
using Lacuna.RestPki.Client;
using Lacuna.RestPki.SampleSite.Classes;
using SampleSite.Classes;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Threading.Tasks;
using System.Web;
using System.Web.Mvc;

namespace Lacuna.RestPki.SampleSite.Controllers {

	public class OpenPadesSignatureBStampController : Controller {

		/**
		 *	This action submits a PDF file to Rest PKI for inspection of its signatures, specifying that
		 *	an "audit package" should be generated.
		 */
		[HttpGet]
		public async Task<ActionResult> Index(string userfile) {

            // Get an instance of the PadesSignatureExplorer class, used to open/validate PDF signatures.
            var sigExplorer = new PadesSignatureExplorer(Util.GetRestPkiClient()) {
                // Specify that we want an audit package to be generated, and that the signed file should be
                // included in the package.
				GenerateAuditPackage = true,
				IncludeSignedFileInAuditPackage = true,
				// In order to generate an audit package, we must also pass Validate = true.
				Validate = true,
                // Specify the parameters for the signature validation:
                // Accept any PAdES signature as long as the signer has an ICP-Brasil certificate.
                DefaultSignaturePolicyId = StandardPadesSignaturePolicies.Basic,
                // We have encapsulated the security context choice on Util.cs.
                SecurityContextId = Util.GetSecurityContextId()
            };

			// Set the PDF file.
			if (string.IsNullOrEmpty(userfile)) {
				// If no file is passed, we use a previously signed and B-Stamped file.
				sigExplorer.SetSignatureFile(Server.MapPath("~/Content/bstamped.pdf"));
			} else {
				sigExplorer.SetSignatureFile(Server.MapPath("~/App_Data/" + userfile.Replace("_", ".")));
			}

			// Call the OpenAsync() method, which returns the signature file's information.
			var signature = await sigExplorer.OpenAsync();

			// If the document has been B-Stamped, store the "digest index file" to show a link on the page.
			if (signature.BStamp != null) {
                string indexFileId;
                using (var indexFileStream = signature.BStamp.IndexFile.OpenRead()) {
                    indexFileId = StorageMock.Store(indexFileStream, ".txt");
                }
				ViewBag.BStampIndexFile = indexFileId;
			}

            // Store the generated audit package. Notice that although we asked for its generation, the
            // signature might not have been B-Stamped yet, so an audit package might not be returned.
			if (signature.AuditPackage != null) {
                string auditPkgId;
                using (var auditPkgStream = signature.AuditPackage.OpenRead()) {
                    auditPkgId = StorageMock.Store(auditPkgStream, ".zip");
                }
				ViewBag.AuditPackageFile = auditPkgId;
			}

            // Render the information. (see file Views/OpenPadesSignatureBStamp/Index.html for more
            // information on the information returned)
			return View(signature);
		}
	}
}

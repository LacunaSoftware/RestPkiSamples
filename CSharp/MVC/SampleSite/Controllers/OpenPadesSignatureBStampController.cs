using Lacuna.RestPki.Api;
using Lacuna.RestPki.Client;
using SampleSite.Classes;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Web;
using System.Web.Mvc;

namespace Lacuna.RestPki.SampleSite.Controllers {

	public class OpenPadesSignatureBStampController : Controller {

		/*
			This action submits a PDF file to Rest PKI for inspection of its signatures, specifying that
			an "audit package" should be generated.
		 */
		[HttpGet]
		public ActionResult Index(string userfile) {

			// Get an instance of the PadesSignatureExplorer class, used to open/validate PDF signatures
			var sigExplorer = new PadesSignatureExplorer(Util.GetRestPkiClient()) {
				// Specify that we want an audit package to be generated, and that the signed file should be included in the package
				GenerateAuditPackage = true,
				IncludeSignedFileInAuditPackage = true,
				// In order to generate an audit package, we must also pass Validate = true
				Validate = true,
			};

			// Set the PDF file
			if (string.IsNullOrEmpty(userfile)) {
				// If no file is passed, we use a previously signed and B-Stamped file
				sigExplorer.SetSignatureFile(Server.MapPath("~/Content/bstamped.pdf"));
			} else {
				var filename = userfile.Replace("_", "."); // Note: we're passing the filename argument with "." as "_" because of limitations of ASP.NET MVC
				sigExplorer.SetSignatureFile(Server.MapPath("~/App_Data/" + filename));
			}

			// Parameters for the signature validation. We have encapsulated this code in a method to include several
			// possibilities depending on the argument passed. Experiment changing the argument to see different validation
			// configurations. Once you decide which is best for your case, you can place the code directly here.
			setValidationParameters(sigExplorer, 1);
			// try changing this number ---------^ for different validation parameters

			// Call the Open() method, which returns the signature file's information
			var signature = sigExplorer.Open();

			// If the document has been B-Stamped, store the "digest index file" to show a link on the page
			if (signature.BStamp != null) {
				var appDataPath = Server.MapPath("~/App_Data");
				if (!Directory.Exists(appDataPath)) {
					Directory.CreateDirectory(appDataPath);
				}
				var id = Guid.NewGuid();
				var filename = id + ".txt";
				signature.BStamp.IndexFile.WriteToFile(Path.Combine(appDataPath, filename));
				ViewBag.BStampIndexFile = filename.Replace(".", "_");
			}

			// Store the generated audit package. Notice that although we asked for its generation,
			// the signature might not have been B-Stamped yet, so an audit package might not be returned.
			if (signature.AuditPackage != null) {
				var appDataPath = Server.MapPath("~/App_Data");
				if (!Directory.Exists(appDataPath)) {
					Directory.CreateDirectory(appDataPath);
				}
				var id = Guid.NewGuid();
				var filename = id + ".zip";
				signature.AuditPackage.WriteToFile(Path.Combine(appDataPath, filename));
				ViewBag.AuditPackageFile = filename.Replace(".", "_");
			}

			// Render the information (see file Views/OpenPadesSignatureBStamp/Index.html for more information on the information returned)
			return View(signature);
		}

		// This function is called by the Index method (see above). It contains examples of signature validation parameters.
		private static void setValidationParameters(PadesSignatureExplorer sigExplorer, int caseNumber) {

			switch (caseNumber) {

				/*
					Example #1: accept any PAdES signature as long as the signer has an ICP-Brasil certificate (RECOMMENDED)

					These parameters will only accept signatures made with ICP-Brasil certificates that comply with the
					minimal security features defined in the PAdES standard (ETSI TS 102 778). The signatures need not, however,
					follow the extra requirements defined in the ICP-Brasil signature policy documentation (DOC-ICP-15.03).

					These are the recommended parameters for ICP-Brasil, since the PAdES policies, released on 2016-06-01,
					are still in adoption phase by most implementors.
				 */
				case 1:
					// By omitting the accepted policies catalog and defining a default policy, we're telling Rest PKI to validate
					// all signatures in the file with the default policy -- even signatures with an explicit signature policy.
					sigExplorer.AcceptableExplicitPolicies = null;
					sigExplorer.DefaultSignaturePolicyId = StandardPadesSignaturePolicies.Basic;
					// The PAdES Basic policy requires us to choose a security context
					sigExplorer.SecurityContextId = StandardSecurityContexts.PkiBrazil;
					break;


				/*
					Example #2: accept only 100%-compliant ICP-Brasil signatures
				 */
				case 2:
					// By specifying a catalog of acceptable policies and omitting the default signature policy, we're telling Rest PKI
					// that only the policies in the catalog should be accepted
					sigExplorer.AcceptableExplicitPolicies = SignaturePolicyCatalog.GetPkiBrazilPades();
					sigExplorer.DefaultSignaturePolicyId = null;
					break;


				/*
					Example #3: accept any PAdES signature as long as the signer is trusted by Windows

					Same case as example #1, but using the WindowsServer trust arbitrator	
				 */
				case 3:
					sigExplorer.AcceptableExplicitPolicies = null;
					sigExplorer.DefaultSignaturePolicyId = StandardPadesSignaturePolicies.Basic;
					sigExplorer.SecurityContextId = StandardSecurityContexts.WindowsServer;
					break;

				/*
					Example #4: accept only 100%-compliant ICP-Brasil signatures that provide signer certificate protection.

					"Signer certificate protection" means that a signature keeps its validity even after the signer certificate
					is revoked or expires. On ICP-Brasil, this translates to policies AD-RT and up (not AD-RB).
				 */
				case 4:
					sigExplorer.AcceptableExplicitPolicies = SignaturePolicyCatalog.GetPkiBrazilPadesWithSignerCertificateProtection();
					sigExplorer.DefaultSignaturePolicyId = null;
					break;
			}
		}
	}
}

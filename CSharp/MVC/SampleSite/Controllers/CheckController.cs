using Lacuna.RestPki.Api;
using Lacuna.RestPki.Client;
using Lacuna.RestPki.SampleSite.Classes;
using Lacuna.RestPki.SampleSite.Models;
using SampleSite.Classes;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading;
using System.Web;
using System.Web.Mvc;

namespace Lacuna.RestPki.SampleSite.Controllers
{
    public class CheckController : Controller
    {
        // GET: Check?code={id}
        public ActionResult Index(string code)
        {

			// On PrinterFriendlyVersionController, we stored the unformatted version of the verification
			// code (without hyphens) but used the formatted version (with hiphens) on the printer-friendly
			// PDF. Now, we remove the hyphens before looking it up.
			var verificationCode = Util.ParseVerificationCode(code);

			// Get document associated with verification code.
			var fileId = StorageMock.LookupVerificationCode(verificationCode);
			if (fileId == null) {
				// Invalid code give!
				// Small delay to slow down brute-force attacks (if you want to be extra careful you might
				// want to add a CAPTCHA to the process).
				Thread.Sleep(TimeSpan.FromSeconds(2));
				// Return Not Found
				return HttpNotFound();
			}

			// Read document from storage.
			var fileContent = StorageMock.Read(fileId);

			// Get an instance of the PadesSignatureExplorer class, used to open/validate PDF signatures.
			var sigExplorer = new PadesSignatureExplorer(Util.GetRestPkiClient()) {
				// Specify that we want to validate the signatures in the file, not only inspect them.
				Validate = true,
				// Specify the parameters for the signature validation:
				// Accept any PAdES signature as long as the signer has an ICP-Brasil certificate.
				DefaultSignaturePolicyId = StandardPadesSignaturePolicies.Basic,
				// Specify the security context to be used to determine trust in the certificate chain. We
				// have encapsulated the security context choice on Util.cs.
				SecurityContextId = Util.GetSecurityContextId()
			};

			// Set the PDF file.
			sigExplorer.SetSignatureFile(fileContent);

			// Call the Open() method, which returns the signature file's information.
			var signature = sigExplorer.Open();

			// Render the information (see file Check/Index.html for more information on
			// the information returned).
			return View(new CheckModel() {
				Signature = signature,
				File = fileId
			});
        }
    }
}
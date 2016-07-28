using Lacuna.RestPki.Api;
using Lacuna.RestPki.Client;
using SampleSite.Classes;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.Mvc;

namespace Lacuna.RestPki.SampleSite.Controllers {

	public class OpenCadesSignatureController : Controller {

		/*
			This action submits a CAdES signature file to Rest PKI for inspection.
		 */
		[HttpGet]
		public ActionResult Index(string userfile) {

			// Our action only works if a userfile is given to work with
			if (string.IsNullOrEmpty(userfile)) {
				return HttpNotFound();
			}
			var filename = userfile.Replace("_", "."); // Note: we're passing the filename argument with "." as "_" because of limitations of ASP.NET MVC

			// Get an instance of the CadesSignatureExplorer class, used to open/validate CAdES signatures
			var sigExplorer = new CadesSignatureExplorer(Util.GetRestPkiClient()) {
				Validate = true // Specify that we want to validate the signatures in the file, not only inspect them
			};

			// Set the CAdES signature file
			sigExplorer.SetSignatureFile(Server.MapPath("~/App_Data/" + filename));

			// Parameters for the signature validation. We have encapsulated this code in a method to include several
			// possibilities depending on the argument passed. Experiment changing the argument to see different validation
			// configurations. Once you decide which is best for your case, you can place the code directly here.
			setValidationParameters(sigExplorer, 1);
			// try changing this number ---------^ for different validation parameters

			// Call the OpenAndExtractContent() method, which returns the signature file's information along with a link to download its encapsulated content
			var signature = sigExplorer.OpenAndExtractContent();

			// Render the information (see file Views/OpenCadesSignature/Index.html for more information on the information returned)
			return View(signature);
		}

		// This function is called by the Index method (see above). It contains examples of signature validation parameters.
		private static void setValidationParameters(CadesSignatureExplorer sigExplorer, int caseNumber) {

			switch (caseNumber) {

				/*
					Example #1: accept only 100%-compliant ICP-Brasil signatures
				 */
				case 1:
					// By specifying a catalog of acceptable policies and omitting the default signature policy, we're telling Rest PKI
					// that only the policies in the catalog should be accepted
					sigExplorer.AcceptableExplicitPolicies = SignaturePolicyCatalog.GetPkiBrazilCades();
					sigExplorer.DefaultSignaturePolicyId = null;
					break;


				/*
					Example #2: accept any CAdES signature as long as the signer has an ICP-Brasil certificate

					These parameters will only accept signatures made with ICP-Brasil certificates that comply with the
					minimal security features defined in the CAdES standard (ETSI TS 101 733). The signatures need not, however,
					follow the extra requirements defined in the ICP-Brasil signature policy documentation (DOC-ICP-15.03).

					These parameters are less restrictive than the parameters from example #1
				 */
				case 2:
					// By omitting the accepted policies catalog and defining a default policy, we're telling Rest PKI to validate
					// all signatures in the file with the default policy -- even signatures with an explicit signature policy.
					sigExplorer.AcceptableExplicitPolicies = null;
					sigExplorer.DefaultSignaturePolicyId = StandardCadesSignaturePolicies.CadesBes;
					// The CadesBes policy requires us to choose a security context
					sigExplorer.SecurityContextId = StandardSecurityContexts.PkiBrazil;
					break;


				/*
					Example #3: accept any CAdES signature as long as the signer is trusted by Windows

					Same case as example #2, but using the WindowsServer trust arbitrator	
				 */
				case 3:
					sigExplorer.AcceptableExplicitPolicies = null;
					sigExplorer.DefaultSignaturePolicyId = StandardCadesSignaturePolicies.CadesBes;
					sigExplorer.SecurityContextId = StandardSecurityContexts.WindowsServer;
					break;

				/*
					Example #4: accept only 100%-compliant ICP-Brasil signatures that provide signer certificate protection.

					"Signer certificate protection" means that a signature keeps its validity even after the signer certificate
					is revoked or expires. On ICP-Brasil, this translates to policies AD-RT and up (but not AD-RB).
				 */
				case 4:
					sigExplorer.AcceptableExplicitPolicies = SignaturePolicyCatalog.GetPkiBrazilCadesWithSignerCertificateProtection();
					sigExplorer.DefaultSignaturePolicyId = null;
					break;

				/*
					Example #5: accept only 100%-compliant ICP-Brasil signatures that provide CA certificate protection (besides signer
					certificate protection).

					"CA certificate protection" means that a signature keeps its validity even after either the signer certificate or
					its Certification Authority (CA) certificate expires or is revoked. On ICP-Brasil, this translates to policies
					AD-RC/AD-RV and up (but not AD-RB nor AD-RT).
				 */
				case 5:
					sigExplorer.AcceptableExplicitPolicies = SignaturePolicyCatalog.GetPkiBrazilCadesWithCACertificateProtection();
					sigExplorer.DefaultSignaturePolicyId = null;
					break;
			}
		}
	}
}

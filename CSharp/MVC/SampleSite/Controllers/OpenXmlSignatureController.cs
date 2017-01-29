using Lacuna.RestPki.Api;
using Lacuna.RestPki.Client;
using SampleSite.Classes;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.Mvc;

namespace Lacuna.RestPki.SampleSite.Controllers
{
    public class OpenXmlSignatureController : BaseController {

		/*
			This action submits a XML file to Rest PKI for inspection of its signatures.
		 */
		[HttpGet]
		public ActionResult Index(string userfile) {

			// Our action only works if a userfile is given to work with
			if (string.IsNullOrEmpty(userfile)) {
				return HttpNotFound();
			}
			var filename = userfile.Replace("_", "."); // Note: we're passing the filename argument with "." as "_" because of limitations of ASP.NET MVC

			// Get an instance of the XmlSignatureExplorer class, used to open/validate XML signatures
			var sigExplorer = new XmlSignatureExplorer(Util.GetRestPkiClient()) {
				Validate = true // Specify that we want to validate the signatures in the file, not only inspect them
			};

			// Set the XML file
			sigExplorer.SetSignatureFile(Server.MapPath("~/App_Data/" + filename));

			// Parameters for the signature validation. We have encapsulated this code in a method to include several
			// possibilities depending on the argument passed. Experiment changing the argument to see different validation
			// configurations. Once you decide which is best for your case, you can place the code directly here.
			setValidationParameters(sigExplorer, 3);
			// try changing this number ---------^ for different validation parameters

			// Call the Open() method, which returns the signature file's information
			var signature = sigExplorer.Open();

			// Render the information (see file Views/OpenXmlSignature/Index.html for more information on the information returned)
			return View(signature);
		}

		// This function is called by the Index method (see above). It contains examples of signature validation parameters.
		private static void setValidationParameters(XmlSignatureExplorer sigExplorer, int caseNumber) {

			switch (caseNumber) {

				/*
					Example #1: accept any XAdES signature as long as the signer has an ICP-Brasil certificate (RECOMMENDED)

					These parameters will only accept signatures made with ICP-Brasil certificates that comply with the
					minimal security features defined in the XAdES standard (ETSI TS 102 778). The signatures need not, however,
					follow the extra requirements defined in the ICP-Brasil signature policy documentation (DOC-ICP-15.03).
				 */
				case 1:
					// By omitting the accepted policies catalog and defining a default policy, we're telling Rest PKI to validate
					// all signatures in the file with the default policy -- even signatures with an explicit signature policy.
					sigExplorer.AcceptableExplicitPolicies = null;
					sigExplorer.DefaultSignaturePolicyId = StandardXmlSignaturePolicies.XadesBes;
					// The XML-DSig Basic policy requires us to choose a security context
					sigExplorer.SecurityContextId = StandardSecurityContexts.PkiBrazil;
					break;


				/*
					Example #2: accept only 100%-compliant ICP-Brasil signatures
				 */
				case 2:
					// By specifying a catalog of acceptable policies and omitting the default signature policy, we're telling Rest PKI
					// that only the policies in the catalog should be accepted
					sigExplorer.AcceptableExplicitPolicies = SignaturePolicyCatalog.GetPkiBrazilXades();
					sigExplorer.DefaultSignaturePolicyId = null;
					break;


				/*
					Example #3: accept any XML D-Sig signature as long as the signer is trusted by Windows

				 */
				case 3:
					sigExplorer.AcceptableExplicitPolicies = null;
					sigExplorer.DefaultSignaturePolicyId = StandardXmlSignaturePolicies.XmlDSigBasic;
					sigExplorer.SecurityContextId = StandardSecurityContexts.PkiBrazil;
					break;
			}
		}
	}
}
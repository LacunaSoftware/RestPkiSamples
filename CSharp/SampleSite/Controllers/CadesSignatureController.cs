using Lacuna.RestPki.Api;
using Lacuna.RestPki.SampleSite.Models;
using SampleSite.Classes;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Web;
using System.Web.Mvc;

namespace Lacuna.RestPki.SampleSite.Controllers {

	public class CadesSignatureController : Controller {

		/*
		 * This action initiates a CAdES signature using REST PKI and renders the signature page.
		 *
		 * Both CAdES signature examples, with a server file and with a file uploaded by the user, converge to this action.
		 * The difference is that, when the file is uploaded by the user, the action is called with a URL argument named "userfile".
		 */
		[HttpGet]
		public ActionResult Index(string userfile, string cmsfile) {

			// Get an instance of the CadesSignatureStarter class, responsible for receiving the signature elements and start the
			// signature process
			var signatureStarter = Util.GetRestPkiClient().GetCadesSignatureStarter();

			// If the user was redirected here by UploadController (signature with file uploaded by user), the "userfile" URL argument
			// will contain the filename under the "App_Data" folder. Otherwise (signature with server file), we'll sign a sample
			// document.
			if (!string.IsNullOrEmpty(userfile)) {
				// Set the path of the file to be signed
				signatureStarter.SetFileToSign(Server.MapPath("~/App_Data/" + userfile.Replace("_", "."))); // Note: we're passing the filename argument with "." as "_" because of limitations of ASP.NET MVC
			} else if (!string.IsNullOrEmpty(cmsfile)) {
				signatureStarter.SetCmsToCoSign(Server.MapPath("~/App_Data/" + cmsfile.Replace("_", "."))); // Note: we're passing the filename argument with "." as "_" because of limitations of ASP.NET MVC
			} else {
				// Set the file to be signed as a byte array
				signatureStarter.SetContentToSign(Util.GetSampleDocContent());
			}

			// Set the signature policy
			signatureStarter.SetSignaturePolicy(StandardCadesSignaturePolicies.PkiBrazil.AdrBasica);

			// Set a SecurityContext to be used to determine trust in the certificate chain
			signatureStarter.SetSecurityContext(new Guid("c2d7fb0b-4f09-4eb1-867a-ff280425f48d")/*StandardSecurityContexts.PkiBrazil*/);
			// Note: By changing the SecurityContext above you can accept only certificates from a certain PKI,
			// for instance, ICP-Brasil (Lacuna.RestPki.Api.StandardSecurityContexts.PkiBrazil).

			// Optionally, set whether the content should be encapsulated in the resulting CMS. If this parameter is ommitted,
			// the following rules apply:
			// - If no CmsToSign is given, the resulting CMS will include the content
			// - If a CmsToCoSign is given, the resulting CMS will include the content if and only if the CmsToCoSign also includes the content
			signatureStarter.SetEncapsulateContent(true);

			// Call the StartWithWebPki() method, which initiates the signature. This yields the token, a 43-character
			// case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
			// signWithRestPki() method on the Web PKI component (see javascript on the view) and also to complete the signature
			// on the POST action below (this should not be mistaken with the API access token).
			var token = signatureStarter.StartWithWebPki();

			// Render the signature page with the token obtained from REST PKI
			return View(new CadesSignatureModel() {
				Token = token,
				UserFile = userfile,
				CmsFile = cmsfile
			});
		}

		/*
		 * This action receives the form submission from the view. We'll call REST PKI to complete the signature.
		 */
		[HttpPost]
		public ActionResult Index(CadesSignatureModel model) {

			// Get an instance of the PadesSignatureFinisher class, responsible for completing the signature process
			var signatureFinisher = Util.GetRestPkiClient().GetCadesSignatureFinisher();

			// Set the token for this signature (rendered in a hidden input field, see the view)
			signatureFinisher.SetToken(model.Token);

			// Call the Finish() method, which finalizes the signature process and returns the signed PDF
			var cms = signatureFinisher.Finish();

			// Get information about the certificate used by the user to sign the file. This method must only be called after
			// calling the Finish() method.
			var signerCert = signatureFinisher.GetCertificateInfo();

			// At this point, you'd typically store the CMS on your database. For demonstration purposes, we'll
			// store the CMS on the App_Data folder and render a page with a link to download the CMS and with the
			// signer's certificate details.

			var appDataPath = Server.MapPath("~/App_Data");
			if (!Directory.Exists(appDataPath)) {
				Directory.CreateDirectory(appDataPath);
			}
			var id = Guid.NewGuid();
			var filename = id + ".p7s";
			System.IO.File.WriteAllBytes(Path.Combine(appDataPath, filename), cms);

			return View("SignatureInfo", new SignatureInfoModel() {
				File = filename.Replace(".", "_"), // Note: we're passing the filename argument with "." as "_" because of limitations of ASP.NET MVC
				SignerCertificate = signerCert
			});
		}

	}
}

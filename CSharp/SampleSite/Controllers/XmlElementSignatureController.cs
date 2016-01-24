using Lacuna.RestPki.Api;
using Lacuna.RestPki.Api.PadesSignature;
using Lacuna.RestPki.Client;
using Lacuna.RestPki.SampleSite.Models;
using SampleSite.Classes;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Web;
using System.Web.Mvc;

namespace Lacuna.RestPki.SampleSite.Controllers {

	public class XmlElementSignatureController : BaseController {

		/*
		 * This action initiates a XML element signature using REST PKI and renders the signature page.
		 * The XML element signature is recommended in cases which there is a need to sign a specific
		 * element of a XML.
		 *
		 * Both XML signature examples, with a server file and with a file uploaded by the user, converge to this action.
		 * The difference is that, when the file is uploaded by the user, the action is called with a URL argument named "userfile".
		 */
		[HttpGet]
		public ActionResult Index() {

			// Instantiate the XmlElementSignatureStarter class, responsible for receiving the signature elements and start the
			// signature process
			var signatureStarter = new XmlElementSignatureStarter(Util.GetRestPkiClient());

			// Set the XML to be signed, a sample Brazilian fiscal invoice pre-generated
			signatureStarter.SetXml(Util.GetSampleNFeContent());

			// Set the ID of the element to be signed
			signatureStarter.SetToSignElementId("NFe35141214314050000662550010001084271182362300");

			// Set the signature policy
			signatureStarter.SetSignaturePolicy(StandardXmlSignaturePolicies.PkiBrazil.NFePadraoNacional);

			// Optionally, set a SecurityContext to be used to determine trust in the certificate chain. Since we're using the
			// StandardXmlSignaturePolicies.PkiBrazil.NFePadraoNacional policy, the security context will default to PKI Brazil (ICP-Brasil)
			// signatureStarter.SetSecurityContext(StandardSecurityContexts.PkiBrazil);
			// Note: By changing the SecurityContext above you can accept only certificates from a certain PKI

			// Call the StartWithWebPki() method, which initiates the signature. This yields the token, a 43-character
			// case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
			// signWithRestPki() method on the Web PKI component (see javascript on the view) and also to complete the signature
			// on the POST action below (this should not be mistaken with the API access token).
			var token = signatureStarter.StartWithWebPki();

			// The token acquired above can only be used for a single signature attempt. In order to retry the signature it is
			// necessary to get a new token. This can be a problem if the user uses the back button of the browser, since the
			// browser might show a cached page that we rendered previously, with a now stale token. To prevent this from happening,
			// we call the method SetNoCacheHeaders() (in BaseController) which sets HTTP headers to prevent caching of the page.
			base.SetNoCacheHeaders();

			// Render the signature page with the token obtained from REST PKI
			return View(new XmlSignatureModel() {
				Token = token
			});
		}

		/*
		 * This action receives the form submission from the view. We'll call REST PKI to complete the signature.
		 */
		[HttpPost]
		public ActionResult Index(XmlSignatureModel model) {

			// Get an instance of the XmlSignatureFinisher class, responsible for completing the signature process
			var signatureFinisher = new XmlSignatureFinisher(Util.GetRestPkiClient());

			// Set the token for this signature (rendered in a hidden input field, see the view)
			signatureFinisher.SetToken(model.Token);

			// Call the Finish() method, which finalizes the signature process and returns the signed PDF
			var signedXml = signatureFinisher.Finish();

			// Get information about the certificate used by the user to sign the file. This method must only be called after
			// calling the Finish() method.
			var signerCert = signatureFinisher.GetCertificateInfo();

			// At this point, you'd typically store the signed XML on your database. For demonstration purposes, we'll
			// store the XML on the App_Data folder and render a page with a link to download the signed XML and with the
			// signer's certificate details.

			var appDataPath = Server.MapPath("~/App_Data");
			if (!Directory.Exists(appDataPath)) {
				Directory.CreateDirectory(appDataPath);
			}
			var id = Guid.NewGuid();
			var filename = id + ".xml";
			System.IO.File.WriteAllBytes(Path.Combine(appDataPath, filename), signedXml);

			return View("SignatureInfo", new SignatureInfoModel() {
				File = filename.Replace(".", "_"), // Note: we're passing the filename argument with "." as "_" because of limitations of ASP.NET MVC
				SignerCertificate = signerCert
			});
		}
	}
}

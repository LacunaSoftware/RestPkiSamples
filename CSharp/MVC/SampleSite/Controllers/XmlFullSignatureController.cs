using Lacuna.RestPki.Api;
using Lacuna.RestPki.Client;
using Lacuna.RestPki.SampleSite.Classes;
using Lacuna.RestPki.SampleSite.Models;
using SampleSite.Classes;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Threading.Tasks;
using System.Web;
using System.Web.Mvc;

namespace Lacuna.RestPki.SampleSite.Controllers {
	public class XmlFullSignatureController : BaseController {

		/**
		 * This action initiates a XML element signature using REST PKI and renders the signature page.
		 * The full XML signature is recommended in cases which there is a need to sign the whole XML file.
		 *
		 * Both XML signature examples, with a server file and with a file uploaded by the user, converge to
         * this action. The difference is that, when the file is uploaded by the user, the action is called
         * with a URL argument named "userfile".
		 */
		[HttpGet]
		public async Task<ActionResult> Index() {

            // Instantiate the XmlElementSignatureStarter class, responsible for receiving the signature
            // elements and start the signature process.
			var signatureStarter = new FullXmlSignatureStarter(Util.GetRestPkiClient());

			// Set the XML to be signed, a sample XML Document.
			signatureStarter.SetXml(Util.GetSampleXmlDocumentPath());

			// Set the signature policy.
			signatureStarter.SetSignaturePolicy(StandardXmlSignaturePolicies.XadesBes);

			// Set the security context to be used to determine trust in the certificate chain. We have
			// encapsulated the security context choice on Util.cs.
			signatureStarter.SetSecurityContext(Util.GetSecurityContextId());

            // Set the location on which to insert the signature node. If the location is not specified, the
            // signature will appended to the root element (which is most usual with enveloped signatures).
			var nsm = new NamespaceManager();
			nsm.AddNamespace("ls", "http://www.lacunasoftware.com/sample");
			signatureStarter.SetSignatureElementLocation("//ls:signaturePlaceholder", Api.XmlSignature.XmlInsertionOptions.AppendChild, nsm);

			// Call the StartWithWebPki() method, which initiates the signature. This yields the token,
			// a 43-character case-sensitive URL-safe string, which identifies this signature process. We'll
			// use this value to call the signWithRestPki() method on the Web PKI component (see 
			// signature-form.js) and also to complete the signature on the POST action below (this should
			// not be mistaken with the API access token).
			var token = await signatureStarter.StartWithWebPkiAsync();

            // The token acquired above can only be used for a single signature attempt. In order to retry
            // the signature it is necessary to get a new token. This can be a problem if the user uses the
            // back button of the browser, since the browser might show a cached page that we rendered
            // previously, with a now stale token. To prevent this from happening, we call the method
            // SetNoCacheHeaders() (in BaseController) which sets HTTP headers to prevent caching of the
            // page.
			base.SetNoCacheHeaders();

			// Render the signature page with the token obtained from REST PKI.
			return View(new XmlSignatureModel() {
				Token = token
			});
		}

		/**
		 * This action receives the form submission from the view. We'll call REST PKI to complete the
         * signature.
		 */
		[HttpPost]
		public async Task<ActionResult> Index(XmlSignatureModel model) {

            // Get an instance of the XmlSignatureFinisher class, responsible for completing the signature
            // process.
            var signatureFinisher = new XmlSignatureFinisher(Util.GetRestPkiClient()) {
                // Set the token for this signature (rendered in a hidden input field, see the view).
                Token = model.Token
            };

            // Call the Finish() method, which finalizes the signature process and returns the signed PDF.
			var signedXml = await signatureFinisher.FinishAsync();

            // Get information about the certificate used by the user to sign the file. This method must only
            // be called after calling the Finish() method.
			var signerCert = signatureFinisher.GetCertificateInfo();

            // At this point, you'd typically store the signed XML on your database. For demonstration
            // purposes, we'll store the PDF on our mock Storage class.
            var fileId = StorageMock.Store(signedXml, ".xml");

			// Render the signature information page.
			return View("SignatureInfo", new SignatureInfoModel() {
                File = fileId,
				SignerCertificate = signerCert
			});
		}

	}
}
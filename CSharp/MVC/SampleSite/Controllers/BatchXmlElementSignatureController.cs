using Lacuna.RestPki.Api;
using Lacuna.RestPki.Client;
using Lacuna.RestPki.SampleSite.Classes;
using Lacuna.RestPki.SampleSite.Models;
using SampleSite.Classes;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using System.Web;
using System.Web.Mvc;

namespace Lacuna.RestPki.SampleSite.Controllers {

	/** 
	 * This controller contains the server-side logic for the batch of XML element signatures on the
	 * same document example.
	 */
	public class BatchXmlElementSignatureController : BaseController {

		/**
		 * GET: BatchXmlElementSignature
		 * 
		 * This action renders the batch signature page.
		 * 
		 * Notice that the only thing we'll do on the server-side at this point is determine the element
		 * IDs of the document to be signed. The page will handl each element one by one and will call the
		 * server asynchronously to start and complete each signature.
		 */
		public ActionResult Index() {
			// It is up to your application's business logic to determine which documents will compose the
			// batch.
			var model = new BatchXmlElementModel() {
				ElementIds = Enumerable.Range(1, 10).Select(i => string.Format("ID2102100000000000000000000000000000000000008916{0:D2}", i)).ToList()
			};
			// Render the bathc signature page.
			return View(model);
		}

		/**
		 * POST: BatchXmlElementSignature/Start
		 * 
		 * This action is called asynchronously from the batch signature page for each XML element being
		 * signed. It receives the ID of the element and the document to be signed. It initiates a XML 
		 * element signature using REST PKI and return the JSON with the token, which identifies this
		 * signature process, to be used in the next signature steps.
		 */
		[HttpPost]
		public async Task<ActionResult> Start(string fileId, string elementId) {

			// Instantiate the XmlElementSignatureStarter class, responsible for receiving the signature
			// elements and start the signature process.
			var signatureStarter = new XmlElementSignatureStarter(Util.GetRestPkiClient());

			if (fileId == null) {
				// Set the XML to be initially signed, a sample manifest.
				signatureStarter.SetXml(Util.GetSampleManifestPath());
			} else {
				// If the XML file is already signed, we pass the signed file isntead of the original file.
				signatureStarter.SetXml(Server.MapPath("~/App_Data/" + fileId.Replace("_", ".")));
			}

			// Set the element ID of the document to be signed.
			signatureStarter.SetToSignElementId(elementId);

			// Set the signature policy.
			signatureStarter.SetSignaturePolicy(StandardXmlSignaturePolicies.PkiBrazil.NFePadraoNacional);

			// Set the security context to be used to determine trust in the certificate chain. We have
			// encapsulated the security context choice on Util.cs.
			signatureStarter.SetSecurityContext(Util.GetSecurityContextId());

			// Call the StartWithWebPki() method, which initiates the signature. This yields the token,
			// a 43-character case-sensitive URL-safe string, which identifies this signature process. We'll
			// use this value to call the signWithRestPki() method on the Web PKI component (see
			// signature-form.js) and also to complete the signature on the POST action below (this should
			// not be mistaken with the API access token).
			var token = await signatureStarter.StartWithWebPkiAsync();

			// Return a JSON with the token obtained from REST PKI (the page will sue jQuery to decode this
			// value).
			return Json(token);
		}

		/**
		 * POST: BatchXmlElementSignature/Complete
		 * 
		 * This action is called asynchronously by the batch siganture page for each document being signed.
		 * It receives the token, that identifies the signature process. We'll call the REST PKI to 
		 * complete this signature and reutrn a JSON with the file that will be signed again.
		 * 
		 * Notice that the "id" is actually the signature process token. We're naming it "id" so that the
         * action can be called as /BatchPadesSignature/Complete
		 */
		[HttpPost]
		public async Task<ActionResult> Complete(string fileId, string token) {

			// Get an instance of the XmlSignatureFinisher class, responsible for completing the signature
			// process.
			var signatureFinisher = new XmlSignatureFinisher(Util.GetRestPkiClient()) {
				// Set the token for this signature.
				Token = token
			};

			// Call the Finish() method, which finalizes the signature process and returns the signed PDF.
			var signedXml = await signatureFinisher.FinishAsync();

			// Get information about the signer's certificate used. This method must only be called after
			// calling the Finish() method.
			var signerCert = signatureFinisher.GetCertificateInfo();

			// At this point, you'd typically store the signed XML on your database. For demonstration
			// puposes, we'll store the PDF on our mock Storage class.

			// If the fileId is not set, a new file created and passed to the next ignatures. This 
			// logic is necessary to use only a single file until all signatures are complete.

			fileId = StorageMock.Store(signedXml, ".xml", fileId);
			// Note: we're passing the filename argument with "." as "_" because of limitations of
			// ASP.NET MVC.

			// Return a JSON with the signed file's id, stored using our mock class (the page will use
			// jQuery to decode this value).
			return Json(fileId);
		}
	}
}
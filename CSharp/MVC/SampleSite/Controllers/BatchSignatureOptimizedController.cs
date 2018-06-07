using Lacuna.RestPki.Api;
using Lacuna.RestPki.Api.PadesSignature;
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

	/**
	 * This controller contains the server-side logic for the optimized batch of PAdES signatures example.
	 *
	 * The logic for the example is more complex than the "regular" batch signature example (controller
     * BatchPadesSignatureController), but the performance is significantly improved (roughly 50% faster).
     *
	 * Notice that the optimized batch example requires a use license for the Web PKI component (every other
     * example in this project	does not). The licensing is not enforced when running on localhost, but in
     * order to run this sample outside of localhost you'll need to set a license on the web.config file.
     * If you need a trial license, please request one at https://www.lacunasoftware.com/en/products/web_pki
	 */
	public class BatchSignatureOptimizedController : BaseController {

		/**
		 * We need to persist information about each batch in progress. For simplificy purposes, we'll store
         * the information	about each batch on a static dictionary (server-side memory). If your application
         * is stateless, you should persist this information on your database instead.
		 */
		private class BatchInfo {
			public string Certificate { get; set; }
		}
		private static Dictionary<Guid, BatchInfo> batches = new Dictionary<Guid, BatchInfo>();

		/**
		 * This action renders the batch signature page.
         *
		 * Notice that the only thing we'll do on the server-side at this point is determine the IDs of the
         * documents to be signed. The page will handle each document one by one and will call the server
         * asynchronously to start and complete each signature.
		 */
		public ActionResult Index() {
            // It is up to your application's business logic to determine which documents will compose the
            // batch.
			var model = new BatchSignatureModel() {
				DocumentIds = Enumerable.Range(1, 30).ToList() // From 1 to 30.
			};
			// Render the batch signature page.
			return View(model);
		}

		/**
		 * This action is called asynchronously to initialize a batch. We'll receive the user's certificate
         * and store it (we'll need this information on each signature, but we'll avoid sending this
         * repeatedly from the view in order to increase performance).
		 */
		[HttpPost]
		public ActionResult Init(BatchSignatureInitRequest request) {
			// Generate a unique ID identifying the batch.
			var batchId = Guid.NewGuid();
			// Store the user's certificate based on the generated ID.
			var batchInfo = new BatchInfo() {
				Certificate = request.Certificate,
			};
			lock (batches) {
				batches[batchId] = batchInfo;
			}

			// Return a JSON with the batch ID (the page will use jQuery to decode this value).
			return Json(new BatchSignatureInitResponse() {
                BatchId = batchId
            });
		}

		/**
		 * This action is called asynchronously in order to initiate the signature of each document in the
         * batch. We'll receive the batch ID and the ID of the current document.
		 */
		[HttpPost]
		public async Task<ActionResult> Start(BatchSignatureStartRequest request) {

			// Recover the batch information based on its ID, which contains the user's certificate.
			var batchInfo = batches[request.BatchId];

            // Get an instance of the PadesSignatureStarter class, responsible for receiving the signature
            // elements and start the signature process.
			var signatureStarter = new PadesSignatureStarter(Util.GetRestPkiClient()) {

                // Set the user's certificate. Notice that this step is not necessary on the regular batch
                // signature example. This enhances the performance of the batch processing.
				SignerCertificate = Convert.FromBase64String(batchInfo.Certificate),

				// Set the signature policy.
				SignaturePolicyId = StandardPadesSignaturePolicies.Basic,

                // Set the security context to be used to determine trust in the certificate chain. We have
                // encapsulated the security context choice on Util.cs.
                SecurityContextId = Util.GetSecurityContextId(),

				// Set a visual representation for the signature.
				VisualRepresentation = PadesVisualElements.GetVisualRepresentation()
			};

			// Set the document to be signed based on its ID (passed to us from the page).
			signatureStarter.SetPdfToSign(Util.GetBatchDocPath(request.DocumentId));

            // Call the Start() method, which initiates the signature. Notice that, on the regular signature
            // example, we call the StartWithRestPki() method, which is simpler but with worse performance.
            // The Start() method will yield not only the token, a 43-character case-sensitive URL-safe
            // string which identifies this signature process, but also the data that should be used to call
            // the signHash() function on the Web PKI component (instead of the signWithRestPki() function,
            // which is also simpler but far slower).
			var signatureParams = await signatureStarter.StartAsync();

            // Notice: it is not necessary to call SetNoCacheHeaders() because this action is a POST action,
            // therefore no caching of the response will be made by browsers.

            // Return a JSON with the token obtained from REST PKI, along with the parameters for the
            // signHash() call (the page will use jQuery to decode this value).
			var response = new BatchSignatureStartResponse() {
				Token = signatureParams.Token,
				ToSignHash = Convert.ToBase64String(signatureParams.ToSignHash),
				DigestAlgorithmOid = signatureParams.DigestAlgorithmOid
			};
			return Json(response);
		}

		/**
		 * This action is called asynchronously in order to complete each document's signature. We'll
         * receive the signature process token previously yielded by REST PKI and the result of the RSA
         * signature performed with Web PKI.
		 */
		[HttpPost]
		public async Task<ActionResult> Complete(BatchSignatureCompleteRequest request) {

            // Get an instance of the PadesSignatureFinisher2 class, responsible for completing the signature
            // process.
			var signatureFinisher = new PadesSignatureFinisher2(Util.GetRestPkiClient()) {

				// Set the token for this signature (rendered in a hidden input field, see the view).
				Token = request.Token,

                // Set the result of the RSA signature. Notice that this call is not necessary on the
                // "regular" batch signature example.
				Signature = Convert.FromBase64String(request.Signature),

			};

            // Call the Finish() method, which finalizes the signature process and returns a
            // SignatureResult object.
			var result = await signatureFinisher.FinishAsync();

            // The "Certificate" property of the SignatureResult object contains information about the
            // certificate used by the user to sign the file.
			var signerCert = result.Certificate;

            // At this point, you'd typically store the signed PDF on your database. For demonstration
            // purposes, we'll store the PDF on our mock Storage class.

            // The SignatureResult object has various methods for writing the signature file to a stream
            // (WriteTo()), local file (WriteToFile()), open a stream to read the content (OpenRead()) and
            // get its contents (GetContent()). For large files, avoid the method GetContent() to avoid
            // memory allocation issues.
            string fileId;
            using (var resultStream = result.OpenRead()) {
                fileId = StorageMock.Store(resultStream, ".pdf");
            }

            // Return a JSON with the signed file's id, stored using our mock class (the page wil use
            // jQuery to decode this value).
			return Json(fileId);
		}
	}
}

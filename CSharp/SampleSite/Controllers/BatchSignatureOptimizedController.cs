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

	/*
		This controller contains the server-side logic for the optimized batch signature example.
	 
		The logic for the example is more complex than the "regular" batch signature example (controller BatchSignatureController),
		but the performance is significantly improved (roughly 50% faster).
	 */
	public class BatchSignatureOptimizedController : BaseController {

		/*
			We need to persist information about each batch in progress. For simplificy purposes, we'll store the information
			about each batch on a static dictionary (server-side memory). If your application is stateless, you should persist
			this information on your database instead.
		 */
		private class BatchInfo {
			public byte[] Certificate { get; set; }
		}
		private static Dictionary<Guid, BatchInfo> batches = new Dictionary<Guid, BatchInfo>();

		/*
			This action renders the batch signature page.

			Notice that the only thing we'll do on the server-side at this point is determine the IDs of the documents
			to be signed. The page will handle each document one by one and will call the server asynchronously to
			start and complete each signature.
		 */
		public ActionResult Index() {
			// It is up to your application's business logic to determine which documents will compose the batch
			var model = new BatchSignatureModel() {
				DocumentIds = Enumerable.Range(1, 30).ToList() // from 1 to 30
			};
			// Render the batch signature page
			return View(model);
		}

		/*
			This action is called asynchronously to initialize a batch. We'll receive the user's certificate and store it
			(we'll need this information on each signature, but we'll avoid sending this repeatedly from the view in order to
			increase performance).
		 */
		[HttpPost]
		public ActionResult Init(BatchSignatureInitRequest request) {
			// Generate a unique ID identifying the batch
			var batchId = Guid.NewGuid();
			// Store the user's certificate based on the generated ID
			var batchInfo = new BatchInfo() {
				Certificate = request.Certificate
			};
			lock (batches) {
				batches[batchId] = batchInfo;
			}
			// Return a JSON with the batch ID (the page will use jQuery to decode this value)
			var response = new BatchSignatureInitResponse() {
				BatchId = batchId
			};
			return Json(response);
		}

		/*
			This action is called asynchronously in order to initiate the signature of each document in the batch. We'll receive
			the batch ID and the ID of the current document.
		 */
		[HttpPost]
		public ActionResult Start(BatchSignatureStartRequest request) {

			// Recover the batch information based on its ID, which contains the user's certificate
			var batchInfo = batches[request.BatchId];

			// Get an instance of the PadesSignatureStarter class, responsible for receiving the signature elements and start the
			// signature process
			var signatureStarter = Util.GetRestPkiClient().GetPadesSignatureStarter();

			// Set the document to be signed based on its ID (passed to us from the page)
			signatureStarter.SetPdfToSign(Util.GetBatchDocContent(request.DocumentId));

			// Set the user's certificate. Notice that this step is not necessary on the regular batch signature example. This
			// enhances the performance of the batch processing
			signatureStarter.SetSignerCertificate(batchInfo.Certificate);

			// Set the signature policy
			signatureStarter.SetSignaturePolicy(StandardPadesSignaturePolicies.Basic);

			// Set a SecurityContext to be used to determine trust in the certificate chain
			signatureStarter.SetSecurityContext(StandardSecurityContexts.PkiBrazil);
			// Note: By changing the SecurityContext above you can accept only certificates from a certain PKI,
			// for instance, ICP-Brasil (Lacuna.RestPki.Api.StandardSecurityContexts.PkiBrazil).

			// Set a visual representation for the signature
			signatureStarter.SetVisualRepresentation(new PadesVisualRepresentation() {
				
				// The tags {{signerName}} and {{signerNationalId}} will be substituted according to the user's certificate
				// signerName -> full name of the signer
				// signerNationalId -> if the certificate is ICP-Brasil, contains the signer's CPF
				Text = new PadesVisualText("Signed by {{signerName}} ({{signerNationalId}})") {
					
					// Specify that the signing time should also be rendered
					IncludeSigningTime = true,

					// Optionally set the horizontal alignment of the text ('Left' or 'Right'), if not set the default is Left
					HorizontalAlign = PadesTextHorizontalAlign.Left

				},
				
				// We'll use as background the image in Content/PdfStamp.png
				Image = new PadesVisualImage(Util.GetPdfStampContent(), "image/png") {

					// Opacity is an integer from 0 to 100 (0 is completely transparent, 100 is completely opaque).
					Opacity = 50,

					// Align the image to the right
					HorizontalAlign = PadesHorizontalAlign.Right

				},

				// Position of the visual representation. We have encapsulated this code in a method to include several
				// possibilities depending on the argument passed. Experiment changing the argument to see different examples
				// of signature positioning. Once you decide which is best for your case, you can place the code directly here.
				Position = getVisualPositioning(1)
			});

			// Call the Start() method, which initiates the signature. Notice that, on the regular signature example, we call the
			// StartWithRestPki() method, which is simpler but with worse performance. The Start() method will yield not only the
			// token, a 43-character case-sensitive URL-safe string which identifies this signature process, but also the data
			// that should be used to call the signHash() function on the Web PKI component (instead of the signWithRestPki()
			// function, which is also simpler but far slower).
			var signatureParams = signatureStarter.Start();

			// Notice: it is not necessary to call SetNoCacheHeaders() because this action is a POST action, therefore no caching
			// of the response will be made by browsers.

			// Return a JSON with the token obtained from REST PKI, along with the parameters for the signHash() call
			// (the page will use jQuery to decode this value)
			var response = new BatchSignatureStartResponse() {
				Token = signatureParams.Token,
				ToSignHash = signatureParams.ToSignHash,
				DigestAlgorithmOid = signatureParams.DigestAlgorithmOid
			};
			return Json(response);
		}

		/*
			This action is called asynchronously in order to complete each document's signature. We'll receive the signature
			process token previously yielded by REST PKI and the result of the RSA signature performed with Web PKI
		 */
		[HttpPost]
		public ActionResult Complete(BatchSignatureCompleteRequest request) {

			// Get an instance of the PadesSignatureFinisher class, responsible for completing the signature process
			var signatureFinisher = Util.GetRestPkiClient().GetPadesSignatureFinisher();

			// Set the token for this signature (rendered in a hidden input field, see the view)
			signatureFinisher.SetToken(request.Token);

			// Set the result of the RSA signature. Notice that this call is not necessary on the "regular" batch signature example
			signatureFinisher.SetSignature(request.Signature);

			// Call the Finish() method, which finalizes the signature process and returns the signed PDF
			var signedPdf = signatureFinisher.Finish();

			// Get information about the certificate used by the user to sign the file. This method must only be called after
			// calling the Finish() method.
			var signerCert = signatureFinisher.GetCertificateInfo();

			// At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
			// store the PDF on the App_Data folder and render a page with a link to download the signed PDF and with the
			// signer's certificate details.

			var appDataPath = Server.MapPath("~/App_Data");
			if (!Directory.Exists(appDataPath)) {
				Directory.CreateDirectory(appDataPath);
			}
			var signedFileId = Guid.NewGuid();
			var filename = signedFileId + ".pdf";
			System.IO.File.WriteAllBytes(Path.Combine(appDataPath, filename), signedPdf);

			var signedFile = filename.Replace(".", "_"); // Note: we're passing the filename argument with "." as "_" because of limitations of ASP.NET MVC
			return Json(signedFile);
		}

		// This function is called by the GET action (see above). It contains examples of signature visual representation positionings.
		private static PadesVisualPositioning getVisualPositioning(int sampleNumber) {

			switch (sampleNumber) {

				case 1:
					// Example #1: automatic positioning on footnote. This will insert the signature, and future signatures,
					// ordered as a footnote of the last page of the document
					return PadesVisualPositioning.GetFootnote(Util.GetRestPkiClient());

				case 2:
					// Example #2: get the footnote positioning preset and customize it
					var footnotePosition = PadesVisualPositioning.GetFootnote(Util.GetRestPkiClient());
					footnotePosition.Container.Left = 2.54;
					footnotePosition.Container.Bottom = 2.54;
					footnotePosition.Container.Right = 2.54;
					return footnotePosition;

				case 3:
					// Example #3: automatic positioning on new page. This will insert the signature, and future signatures,
					// in a new page appended to the end of the document.
					return PadesVisualPositioning.GetNewPage(Util.GetRestPkiClient());

				case 4:
					// Example #4: get the "new page" positioning preset and customize it
					var newPagePos = PadesVisualPositioning.GetNewPage(Util.GetRestPkiClient());
					newPagePos.Container.Left = 2.54;
					newPagePos.Container.Top = 2.54;
					newPagePos.Container.Right = 2.54;
					newPagePos.SignatureRectangleSize.Width = 5;
					newPagePos.SignatureRectangleSize.Height = 3;
					return newPagePos;

				case 5:
					// Example #5: manual positioning
					// The first parameter is the page number. Zero means the signature will be placed on a new page appended to the end of the document
					return new PadesVisualManualPositioning(0, PadesMeasurementUnits.Centimeters, new PadesVisualRectangle() {
						// define a manual position of 5cm x 3cm, positioned at 1 inch from  the left and bottom margins
						Left = 2.54,
						Bottom = 2.54,
						Width = 5,
						Height = 3
					});

				case 6:
					// Example #6: custom auto positioning
					return new PadesVisualAutoPositioning() {
						PageNumber = -1, // negative values represent pages counted from the end of the document (-1 is last page)
						MeasurementUnits = PadesMeasurementUnits.Centimeters,
						// Specification of the container where the signatures will be placed, one after the other
						Container = new PadesVisualRectangle() {
							// Specifying left and right (but no width) results in a variable-width container with the given margins
							Left = 2.54,
							Right = 2.54,
							// Specifying bottom and height (but no top) results in a bottom-aligned fixed-height container
							Bottom = 2.54,
							Height = 12.31
						},
						// Specification of the size of each signature rectangle
						SignatureRectangleSize = new PadesSize(5, 3),
						// The signatures will be placed in the container side by side. If there's no room left, the signatures
						// will "wrap" to the next row. The value below specifies the vertical distance between rows
						RowSpacing = 1
					};

				default:
					return null;
			}
		}
	}
}

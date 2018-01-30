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
	 * This controller contains the server-side logic for the batch signature example.
	 */
	public class BatchSignatureController : BaseController {

		/**
         * GET: BatchSignature
         * 
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
         * POST: BatchSignature/Start
         * 
		 * This action is called asynchronously from the batch signature page in order to initiate the
         * signature of each document in the batch.
		 */
		[HttpPost]
		public async Task<ActionResult> Start(int id) {

            // Get an instance of the PadesSignatureStarter class, responsible for receiving the signature
            // elements and start the signature process.
			var signatureStarter = new PadesSignatureStarter(Util.GetRestPkiClient()) {

				// Set the signature policy.
				SignaturePolicyId = StandardPadesSignaturePolicies.Basic,

                // Set a SecurityContext to be used to determine trust in the certificate chain. We have
                // encapsulated the security context choice on Util.cs.
                SecurityContextId = Util.GetSecurityContextId(),

				// Set a visual representation for the signature.
				VisualRepresentation = new PadesVisualRepresentation() {

                    // The tags {{name}} and {{national_id}} will be substituted according to the user's
                    // certificate:
                    //
                    //      name        : Full name of the signer;
                    //      national_id : If the certificate is ICP-Brasil, contains the signer's CPF.
                    //
                    // For a full list of the supported tags, see:
                    // https://github.com/LacunaSoftware/RestPkiSamples/blob/master/PadesTags.md
                    Text = new PadesVisualText("Signed by {{name}} ({{national_id}})") {

						// Specify that the signing time should also be rendered.
						IncludeSigningTime = true,

                        // Optionally set the horizontal alignment of the text ('Left' or 'Right'), if not
                        // set the default is Left.
						HorizontalAlign = PadesTextHorizontalAlign.Left

					},

					// We'll use as background the image in Content/PdfStamp.png.
					Image = new PadesVisualImage(Util.GetPdfStampContent(), "image/png") {

                        // Opacity is an integer from 0 to 100 (0 is completely transparent, 100 is
                        // completely opaque).
						Opacity = 50,

						// Align the image to the right
						HorizontalAlign = PadesHorizontalAlign.Right

					},

                    // Position of the visual representation. We have encapsulated this code in a method to
                    // include several possibilities depending on the argument passed. Experiment changing
                    // the argument to see different examples of signature positioning. Once you decide which
                    // is best for your case, you can place the code directly here.
					Position = PadesVisualElements.GetVisualPositioning(1)
				}
			};

			// Set the document to be signed based on its ID. (passed to us from the page)
			signatureStarter.SetPdfToSign(Util.GetBatchDocPath(id));

            /*
				Optionally, add marks to the PDF before signing. These differ from the signature visual
                representation in that they are actually changes done to the document prior to signing, not 
                binded to any signature. Therefore, any number of marks can be added, for instance one per
                page, whereas there can only be one visual representation per signature. However, since the
                marks are in reality changes to the PDF, they can only be added to documents which have no
                previous signatures, otherwise such signatures would be made invalid by the changes to the
                document (see property PadesSignatureStarter.BypassMarksIfSigned). This problem does not
                occurr with signature visual representations.
			
				We have encapsulated this code in a method to include several possibilities depending on the
                argument passed. Experiment changing the argument to see different examples of PDF marks.
                Once you decide which is best for your case, you can place the code directly here.
			*/
            //signatureStarter.PdfMarks.Add(PadesVisualElements.GetPdfMark(1));

            // Call the StartWithWebPkiAsync() method, which initiates the signature. This yields the token,
            // a 43-character case-sensitive URL-safe string, which identifies this signature process. We'll
            // use this value to call the signWithRestPki() method on the Web PKI component (see javascript
            // on the view) and also to complete the signature on the POST action below (this should not be
            // mistaken with the API access token).
            var token = await signatureStarter.StartWithWebPkiAsync();

            // Notice: it is not necessary to call SetNoCacheHeaders() because this action is a POST action,
            // therefore no caching of the response will be made by browsers.

            // Return a JSON with the token obtained from REST PKI. (the page will use jQuery to decode this 
            // value)
			return Json(token);
		}

		/**
         * POST: BatchSignature/Complete
         * 
		 * This action receives the form submission from the view. We'll call REST PKI to complete the
         * signature.
		 *
		 * Notice that the "id" is actually the signature process token. We're naming it "id" so that the
         * action can be called as /BatchSignature/Complete/{token}
		 */
		[HttpPost]
		public async Task<ActionResult> Complete(string id) {

            // Get an instance of the PadesSignatureFinisher2 class, responsible for completing the signature
            // process.
			var signatureFinisher = new PadesSignatureFinisher2(Util.GetRestPkiClient()) {

				// Set the token for this signature. (rendered in a hidden input field, see the view)
				Token = id

			};

            // Call the FinishAsync() method, which finalizes the signature process and returns a
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

		    // Return a JSON with the signed file's id, stored using our mock class. (the page will use
            // jQuery to decode this value)
			return Json(fileId);
		}
	}
}

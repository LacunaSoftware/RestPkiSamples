using Lacuna.RestPki.Api;
using Lacuna.RestPki.Api.PadesSignature;
using Lacuna.RestPki.Client;
using SampleSite.Classes;
using SampleSite.Models;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Net;
using System.Net.Http;
using System.Threading.Tasks;
using System.Web.Hosting;
using System.Web.Http;
using System.Web.Http.Description;

namespace SampleSite.Api {

	/**
	 * This controller contains the server-side logic for the PAdES signature example. The client-side is implemented at:
	 * - HTML: Views/Home/PadesSignature.cshtml
	 * - JS: Content/js/app/pades-signature.js
	 */
	public class PadesSignatureController : ApiController {

		/**
		 * GET Api/PadesSignature
		 * 
		 * This action is called by the page to initiate the signature process.
		 */
		[HttpGet, Route("api/PadesSignature")]
		public async Task<IHttpActionResult> Get() {

			// Get an instance of the PadesSignatureStarter class, responsible for receiving the signature elements and start the
			// signature process
			var signatureStarter = Util.GetRestPkiClient().GetPadesSignatureStarter();

			// Set the PDF to be signed, which in the case of this example is a fixed sample document
			signatureStarter.SetPdfToSign(Util.GetSampleDocContent());

			// Set the signature policy
			signatureStarter.SetSignaturePolicy(StandardPadesSignaturePolicies.Basic);

			// Set a SecurityContext to be used to determine trust in the certificate chain
			signatureStarter.SetSecurityContext(Util.SecurityContextId);
			// Note: By changing the SecurityContext above you can accept only certificates from a certain PKI,
			// for instance, ICP-Brasil (Lacuna.RestPki.Api.StandardSecurityContexts.PkiBrazil).

			// Set a visual representation for the signature
			var visualRep = new PadesVisualRepresentation() {
            // The tags {{signerName}} and {{signerNationalId}} will be substituted according to the user's certificate
            // signerName -> full name of the signer
            // signerNationalId -> if the certificate is ICP-Brasil, contains the signer's CPF
            Text = new PadesVisualText("Assinado por {{signerName}} (CPF {{signerNationalId}})") {
               // Specify that the signing time should also be rendered
               IncludeSigningTime = true,
               // Optionally set the horizontal alignment of the text ('Left' or 'Right'), if not set the default is Left
               HorizontalAlign = PadesTextHorizontalAlign.Left
            },
				Image = new PadesVisualImage(Util.GetPdfStampContent(), "image/png"),
				Position = await getVisualPositioning(4) // changing this number will result in different examples of signature positioning being used
			};
			signatureStarter.SetVisualRepresentation(visualRep);

			// Call the startWithWebPki, which initiates the signature. This yields the token,
			// a 43-character case-sensitive URL-safe string, which we'll send to the page in order to pass on the
			// signWithRestPki method of the Web PKI component.
			var token = await signatureStarter.StartWithWebPkiAsync();

			// Return the token to the page
			return Ok(token);
		}

		// This function is called by the get() function. It contains examples of signature visual representation positionings.
		private static async Task<PadesVisualPositioning> getVisualPositioning(int sampleNumber) {

			switch (sampleNumber) {

				case 1:
					// Example #1: automatic positioning on footnote. This will insert the signature, and future signatures, 
					// ordered as a footnote of the last page of the document
					return await PadesVisualPositioning.GetFootnoteAsync(Util.GetRestPkiClient());

				case 2:
					// Example #2: get the footnote positioning preset and customize it
					var footnotePosition = await PadesVisualPositioning.GetFootnoteAsync(Util.GetRestPkiClient());
					footnotePosition.Container.Bottom = 3;
					return footnotePosition;

				case 3:
					// Example #3: manual positioning
					// The first parameter is the page number. Negative numbers represent counting from end of the document (-1 is last page)
					return new PadesVisualManualPositioning(-1, PadesMeasurementUnits.Centimeters, new PadesVisualRectangle() {
						// define a manual position of 5cm x 3cm, positioned at 1 inch from  the left and bottom margins
						Left = 2.54,
						Bottom = 2.54,
						Width = 5,
						Height = 3
					});

				case 4:
					// Example #4: auto positioning
					return new PadesVisualAutoPositioning() {
						PageNumber = -1,
						MeasurementUnits = PadesMeasurementUnits.Centimeters,
						Container = new PadesVisualRectangle() {
							Left = 2.54,
							Bottom = 2.54,
							Right = 2.54,
							Height = 12.31
						},
						SignatureRectangleSize = new PadesSize(5, 3),
						RowSpacing = 1
					};

				default:
					return null;
			}
		}

		/**
		 * POST Api/PadesSignature/{token}
		 * 
		 * This action is called once the signature is complete on the client-side. The page sends back on the URL the token
		 * originally yielded by the get() method.
		 */
		[HttpPost, Route("api/PadesSignature/{token}")]
		public async Task<IHttpActionResult> Post(string token) {

			// Get an instance of the PadesSignatureFinisher class, responsible for completing the signature process
			var signatureFinisher = Util.GetRestPkiClient().GetPadesSignatureFinisher();

			// Set the token previously yielded by the startWithWebPki() method (which we sent to the page and the page
			// sent us back on the URL)
			signatureFinisher.SetToken(token);

			byte[] signedPdf;
			try {

				// Call the finish() method, which finalizes the signature process. Unlike the complete() method of the
				// Authentication class, this method throws an exception if validation of the signature fails.
				signedPdf = await signatureFinisher.FinishAsync();

			} catch (ValidationException ex) {

				// If validation of the signature failed, inform the page
				return Ok(new SignatureCompleteResponse() {
					Success = false,
					Message = "A validation error has occurred",
					ValidationResults = ex.ValidationResults.ToString()
				});

			}

			// At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
			// store the PDF on the App_Data folder and send to the page an ID that can be used to open the signed PDF.

			var id = Guid.NewGuid();
			var appDataPath = HostingEnvironment.MapPath("~/App_Data");
			if (!Directory.Exists(appDataPath)) {
				Directory.CreateDirectory(appDataPath);
			}
			File.WriteAllBytes(Path.Combine(appDataPath, id + ".pdf"), signedPdf);

			return Ok(new SignatureCompleteResponse() {
				Success = true,
				SignatureId = id
			});
		}

	}
}

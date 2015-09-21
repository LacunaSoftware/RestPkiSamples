using Lacuna.RestPki.Api;
using Lacuna.RestPki.Api.PadesSignature;
using Lacuna.RestPki.Client;
using SampleSite.Classes;
using SampleSite.Models;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Net;
using System.Net.Http;
using System.Threading.Tasks;
using System.Web.Http;
using System.Web.Http.Description;

namespace SampleSite.Api {

	/**
	 * This controller contains the server-side logic for the PAdES signature example. The client-side is implemented at:
	 * - HTML: Views/Home/PadesSignature.cshtml
	 * - JS: Content/js/app/pades-signature.js
	 * 
	 * This controller implements the logic described at
	 * http://pki.lacunasoftware.com/Help/html/c5494b89-d573-4a35-a911-721e32b08dd9.htm
	 * 
	 * Note on encodings: the models used in this controller declare byte arrays, but on the
	 * javascript the values are Base64-encoded. ASP.NET's Web API framework is taking care of
	 * the conversions for us. However, if another technology is to be used, such as MVC, the
	 * conversion might have to be done manually on the server-side.
	 * 
	 * NOTE: there's an important piece of code regaring PAdES signatures on the website startup (see Global.asax)
	 */
	public class PadesSignatureController : ApiController {

      /**
		 * POST Api/PadesSignature/Start
		 * 
		 * This action is called once the user's certificate encoding has been read, and contains the
		 * logic to prepare the byte array that needs to be actually signed with the user's private key
		 * (the "to-sign-bytes").
		 */
      [HttpGet, Route("api/PadesSignature")]
      public async Task<IHttpActionResult> Get() {

         var signatureStarter = Util.GetRestPkiClient().GetPadesSignatureStarter();
			signatureStarter.SetPdfToSign(Util.GetSampleDocContent());
			signatureStarter.SetSignaturePolicy(StandardPadesSignaturePolicies.Basic);
			signatureStarter.SetSecurityContext(Util.SecurityContextId);

			var visualRep = new PadesVisualRepresentation() {
				Text = new PadesVisualText("Assinado por {{signerName}} (CPF {{signerNationalId}})", true),
				Image = new PadesVisualImage(Util.GetPdfStampContent(), "image/png"),
            Position = await getVisualPositioning(4)
			};
			signatureStarter.SetVisualRepresentation(visualRep);

         var token = await signatureStarter.StartWithWebPkiAsync();
			return Ok(token);
		}

		private static async Task<PadesVisualPositioning> getVisualPositioning(int sampleNumber) {

			switch (sampleNumber) {

				case 1:
					return await PadesVisualPositioning.GetFootnoteAsync(Util.GetRestPkiClient());

				case 2:
					var footnotePosition = await PadesVisualPositioning.GetFootnoteAsync(Util.GetRestPkiClient());
					footnotePosition.Container.Bottom = 3;
					return footnotePosition;

				case 3:
					return new PadesVisualManualPositioning(-1, PadesMeasurementUnits.Centimeters, new PadesVisualRectangle() {
						Left = 2.54,
						Bottom = 2.54,
						Width = 5,
						Height = 3
					});

				case 4:
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
		 * POST Api/PadesSignature/Complete
		 * 
		 * This action is called once the "to-sign-bytes" are signed using the user's certificate. The
		 * page sends back the SignatureProcess ID and the signature operation result.
		 */
		[HttpPost, Route("api/PadesSignature/{token}")]
		public async Task<IHttpActionResult> Post(string token) {

			var signatureFinisher = Util.GetRestPkiClient().GetPadesSignatureFinisher();
			signatureFinisher.SetToken(token);

			byte[] signedPdf;
			try {
            signedPdf = await signatureFinisher.FinishAsync();
			} catch (ValidationException ex) {
				return Ok(new SignatureCompleteResponse() {
					Success = false,
					Message = "A validation error has occurred",
					ValidationResults = ex.ValidationResults.ToString()
				});
			}

			// Store the signature for future download (see method SignatureController.Download in the Controllers folder)
			Signature signature;
			using (var dbContext = new DbContext()) {
				signature = Signature.Create();
				signature.Type = SignatureTypes.Pades;
				signature.Content = signedPdf;
				dbContext.Signatures.Add(signature);
				dbContext.SaveChanges();
			}

			// Inform the page of the success, along with the ID of the stored signature, so that the page
			// can render the download link
			return Ok(new SignatureCompleteResponse() {
				Success = true,
				SignatureId = signature.Id
			});
		}

	}
}

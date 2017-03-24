using CoreWebApp.Classes;
using CoreWebApp.Models;
using Lacuna.RestPki.Api;
using Lacuna.RestPki.Api.PadesSignature;
using Lacuna.RestPki.Client;
using Microsoft.AspNetCore.Hosting;
using Microsoft.AspNetCore.Mvc;
using Microsoft.Extensions.Options;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;

namespace CoreWebApp.Controllers {

	[Route("api/[controller]")]
	public class PadesSignatureController : Controller {

		private IHostingEnvironment hostingEnvironment;
		private RestPkiConfig restPkiConfig;

		public PadesSignatureController(IHostingEnvironment hostingEnvironment, IOptions<RestPkiConfig> optionsAccessor) {
			this.hostingEnvironment = hostingEnvironment;
			this.restPkiConfig = optionsAccessor.Value;
		}

		[HttpPost]
		public async Task<string> Start(string userfile) {

			var storage = new Storage(hostingEnvironment);
			var client = Util.GetRestPkiClient(restPkiConfig);

			// Get an instance of the PadesSignatureStarter class, responsible for receiving the signature elements and start the
			// signature process
			var signatureStarter = new PadesSignatureStarter(client) {

				// Set the unit of measurement used to edit the pdf marks and visual representations
				MeasurementUnits = PadesMeasurementUnits.Centimeters,

				// Set the signature policy
				SignaturePolicyId = StandardPadesSignaturePolicies.Basic,

				// Set a SecurityContext to be used to determine trust in the certificate chain
				SecurityContextId = StandardSecurityContexts.PkiBrazil,
				// Note: By changing the SecurityContext above you can accept certificates from a custom security context created on the Rest PKI website.

				// Set a visual representation for the signature
				VisualRepresentation = new PadesVisualRepresentation() {

					// The tags {{name}} and {{br_cpf_formatted}} will be substituted according to the user's certificate
					//
					//		name             : full name of the signer
					//		br_cpf_formatted : if the certificate is ICP-Brasil, contains the signer's CPF
					//
					// For a full list of the supported tags, see: https://github.com/LacunaSoftware/RestPkiSamples/blob/master/PadesTags.md
					Text = new PadesVisualText("Signed by {{name}} ({{br_cpf_formatted}})") {

						// Specify that the signing time should also be rendered
						IncludeSigningTime = true,

						// Optionally set the horizontal alignment of the text ('Left' or 'Right'), if not set the default is Left
						HorizontalAlign = PadesTextHorizontalAlign.Left

					},

					// We'll use as background the image in Content/PdfStamp.png
					Image = new PadesVisualImage(storage.GetPdfStampContent(), "image/png") {

						// Opacity is an integer from 0 to 100 (0 is completely transparent, 100 is completely opaque).
						Opacity = 50,

						// Align the image to the right
						HorizontalAlign = PadesHorizontalAlign.Right

					},

					// Position of the visual representation. We have encapsulated this code in a method to include several
					// possibilities depending on the argument passed. Experiment changing the argument to see different examples
					// of signature positioning. Once you decide which is best for your case, you can place the code directly here.
					Position = PadesVisualElements.GetVisualPositioning(client, 1)

				}

			};

			// If the "userfile" URL argument is set, it will contain the filename under the "App_Data" folder. Otherwise 
			// (signature with server file), we'll sign a sample document.
			if (string.IsNullOrEmpty(userfile)) {
				// Set the PDF to be signed as a byte array
				signatureStarter.SetPdfToSign(storage.GetSampleDocPath());
			} else {
				//Set the path of the file to be signed
				byte[] content;
				if (!storage.TryOpenRead(userfile, out content)) {
					throw new Exception("File not found");
				}
				signatureStarter.SetPdfToSign(content);
			}

			/*
			Optionally, add marks to the PDF before signing. These differ from the signature visual representation in that
			they are actually changes done to the document prior to signing, not binded to any signature. Therefore, any number
			of marks can be added, for instance one per page, whereas there can only be one visual representation per signature.
			However, since the marks are in reality changes to the PDF, they can only be added to documents which have no previous
			signatures, otherwise such signatures would be made invalid by the changes to the document (see property
			PadesSignatureStarter.BypassMarksIfSigned). This problem does not occurr with signature visual representations.

			We have encapsulated this code in a method to include several possibilities depending on the argument passed.
			Experiment changing the argument to see different examples of PDF marks. Once you decide which is best for your case,
			you can place the code directly here.
		*/
			//signatureStarter.PdfMarks.Add(PadesVisualElements.GetPdfMark(storage, 1));

			var token = await signatureStarter.StartWithWebPkiAsync();

			return token;
		}

		[HttpPost("{token}")]
		public async Task<SignatureCompleteResponse> Complete(string token) {

			var storage = new Storage(hostingEnvironment);
			var client = Util.GetRestPkiClient(restPkiConfig);

			// Get an instance of the PadesSignatureFinisher2 class, responsible for completing the signature process
			var signatureFinisher = new PadesSignatureFinisher2(client) {

				// Set the token for this signature (rendered in a hidden input field, see the view)
				Token = token

			};

			// Call the FinishAsync() method, which finalizes the signature process and returns a SignatureResult object
			var signatureResult = await signatureFinisher.FinishAsync();

			// The "Certificate" property of the SignatureResult object contains information about the certificate used by the user
			// to sign the file.
			var signerCert = signatureResult.Certificate;

			// At this point, you'd typically store the signed PDF on a database or storage service. For demonstration purposes, we'll
			// store the PDF on our "storage mock", which in turn stores the PDF on the App_Data folder.

			// The SignatureResult object has various methods for writing the signature file to a stream (WriteTo()), local file (WriteToFile()), open
			// a stream to read the content (OpenRead()) and get its contents (GetContent()). For large files, avoid the method GetContent() to avoid
			// memory allocation issues.
			string filename;
			using (var signatureStream = await signatureResult.OpenReadAsync()) {
				filename = await storage.StoreAsync(signatureStream, ".pdf");
			}

			// Pass the following fields to be used on signature-results template:
			// - The signature filename, which can be used to provide a link to the file
			// - The user's certificate
			var response = new SignatureCompleteResponse() {
				Filename = filename,
				Certificate = new Models.CertificateModel(signerCert)
			};

			return response;
		}

	}
}

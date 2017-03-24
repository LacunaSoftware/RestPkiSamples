using Lacuna.RestPki.Api;
using Lacuna.RestPki.Api.PadesSignature;
using Lacuna.RestPki.Client;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;

namespace WebForms {

	public partial class PadesSignature : System.Web.UI.Page {

		protected string UserFile { get; private set; }
		public string SignatureFilename { get; private set; }
		public PKCertificate SignerCertificate { get; private set; }

		protected void Page_Load(object sender, EventArgs e) {
			if (!IsPostBack) {
				// Get an instance of the PadesSignatureStarter class, responsible for receiving the signature elements and start the
				// signature process
				var signatureStarter = Util.GetRestPkiClient().GetPadesSignatureStarter();

				// Set the unit of measurement used to edit the pdf marks and visual representations
				signatureStarter.MeasurementUnits = PadesMeasurementUnits.Centimeters;

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
					Position = PadesVisualElements.GetVisualPositioning(1)
				});

				// If the user was redirected here by Upload (signature with file uploaded by user), the "userfile" URL argument
				// will contain the filename under the "App_Data" folder. Otherwise (signature with server file), we'll sign a sample
				// document.
				UserFile = Request.QueryString["userfile"];
				if (string.IsNullOrEmpty(UserFile)) {
					// Set the PDF to be signed as a byte array
					signatureStarter.SetPdfToSign(Util.GetSampleDocContent());
				} else {
					// Set the path of the file to be signed
					signatureStarter.SetPdfToSign(Server.MapPath("~/App_Data/" + UserFile.Replace("_", ".")));
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
				//signatureStarter.PdfMarks.Add(PadesVisualElements.GetPdfMark(1));

				// Call the StartWithWebPki() method, which initiates the signature. This yields the token, a 43-character
				// case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
				// signWithRestPki() method on the Web PKI component (see javascript on the view) and also to complete the signature
				// on the POST action below (this should not be mistaken with the API access token).
				var token = signatureStarter.StartWithWebPki();

				ViewState["Token"] = token;

			}
		}

		protected void SubmitButton_Click(object sender, EventArgs e) {
			// Get an instance of the PadesSignatureFinisher class, responsible for completing the signature process
			var signatureFinisher = Util.GetRestPkiClient().GetPadesSignatureFinisher();

			// Set the token for this signature (rendered in a hidden input field, see the view)
			signatureFinisher.SetToken((string)ViewState["Token"]);

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
			var id = Guid.NewGuid();
			var filename = id + ".pdf";
			System.IO.File.WriteAllBytes(Path.Combine(appDataPath, filename), signedPdf);

			this.SignatureFilename = filename.Replace(".", "_"); // Note: we're passing the filename argument with "." as "_" because of limitations of ASP.NET MVC
			this.SignerCertificate = signerCert;

			Server.Transfer("PadesSignatureInfo.aspx");

		}

	}
}
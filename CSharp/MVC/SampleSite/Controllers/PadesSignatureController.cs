using Lacuna.RestPki.Api;
using Lacuna.RestPki.Api.PadesSignature;
using Lacuna.RestPki.Client;
using Lacuna.RestPki.SampleSite.Classes;
using Lacuna.RestPki.SampleSite.Models;
using SampleSite.Classes;
using System;
using System.IO;
using System.Web.Mvc;

namespace Lacuna.RestPki.SampleSite.Controllers {

	public class PadesSignatureController : BaseController {

		/*
		 * This action initiates a PAdES signature using REST PKI and renders the signature page.
		 *
		 * Both PAdES signature examples, with a server file and with a file uploaded by the user, converge to this action.
		 * The difference is that, when the file is uploaded by the user, the action is called with a URL argument named "userfile".
		 */
		[HttpGet]
		public ActionResult Index(string userfile) {

			// Get an instance of the PadesSignatureStarter class, responsible for receiving the signature elements and start the
			// signature process
			var signatureStarter = new PadesSignatureStarter(Util.GetRestPkiClient()) {

				// Set the unit of measurement used to edit the pdf marks and visual representations
				MeasurementUnits = PadesMeasurementUnits.Centimeters,

				// Set the signature policy
				SignaturePolicyId = StandardPadesSignaturePolicies.Basic,

				// Set a SecurityContext to be used to determine trust in the certificate chain
				SecurityContextId = StandardSecurityContexts.PkiBrazil,
				// Note: By changing the SecurityContext above you can accept certificates from a custom security context created on the Rest PKI website.

				// Set a visual representation for the signature
				VisualRepresentation = new PadesVisualRepresentation() {

					// The tags {{signerName}} and {{signerNationalId}} will be substituted according to the user's certificate
					// signerName -> full name of the signer
					// signerNationalId -> if the certificate is ICP-Brasil, contains the signer's CPF
					Text = new PadesVisualText("name: \"{{name}}\", national_id: \"{{national_id}}\", email: \"{{email}}\", subject_cn: \"{{subject_cn}}\", issuer_cn: \"{{issuer_cn}}\", br_cpf: \"{{br_cpf}}\", br_cpf_formatted: \"{{br_cpf_formatted}}\", br_cnpj: \"{{br_cnpj}}\", br_cnpj_formatted: \"{{br_cnpj_formatted}}\", br_responsavel: \"{{br_responsavel}}\", br_company: \"{{br_company}}\", br_oab_numbero: \"{{br_oab_numbero}}\", br_oab_uf: \"{{br_oab_uf}}\", br_rg_numero: \"{{br_rg_numero}}\", br_rg_emissor: \"{{br_rg_emissor}}\", br_rg_uf: \"{{br_rg_uf}}\", signerName: \"{{signerName}}\", signerEmail: \"{{signerEmail}}\", signerNationalId: \"{{signerNationalId}}\", issuerCommonName: \"{{issuerCommonName}}\"") {

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
				},
			};

			// If the user was redirected here by UploadController (signature with file uploaded by user), the "userfile" URL argument
			// will contain the filename under the "App_Data" folder. Otherwise (signature with server file), we'll sign a sample
			// document.
			if (string.IsNullOrEmpty(userfile)) {
				// Set the PDF to be signed as a byte array
				signatureStarter.SetPdfToSign(Util.GetSampleDocContent());
			} else {
				// Set the path of the file to be signed
				signatureStarter.SetPdfToSign(Server.MapPath("~/App_Data/" + userfile.Replace("_", "."))); // Note: we're passing the filename argument with "." as "_" because of limitations of ASP.NET MVC
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

			// The token acquired above can only be used for a single signature attempt. In order to retry the signature it is
			// necessary to get a new token. This can be a problem if the user uses the back button of the browser, since the
			// browser might show a cached page that we rendered previously, with a now stale token. To prevent this from happening,
			// we call the method SetNoCacheHeaders() (in BaseController) which sets HTTP headers to prevent caching of the page.
			base.SetNoCacheHeaders();

			// Render the signature page with the token obtained from REST PKI
			return View(new Models.PadesSignatureModel() {
				Token = token,
				UserFile = userfile
			});
		}

		/*
		 * This action receives the form submission from the view. We'll call REST PKI to complete the signature.
		 */
		[HttpPost]
		public ActionResult Index(Models.PadesSignatureModel model) {

			// Get an instance of the PadesSignatureFinisher2 class, responsible for completing the signature process
			var signatureFinisher = new PadesSignatureFinisher2(Util.GetRestPkiClient()) {

				// Set the token for this signature (rendered in a hidden input field, see the view)
				Token = model.Token

			};

			// Call the Finish() method, which finalizes the signature process and returns a SignatureResult object
			var signatureResult = signatureFinisher.Finish();

			// The "Certificate" property of the SignatureResult object contains information about the certificate used by the user
			// to sign the file.
			var signerCert = signatureResult.Certificate;

			// At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
			// store the PDF on the App_Data folder and render a page with a link to download the signed PDF and with the
			// signer's certificate details.

			var appDataPath = Server.MapPath("~/App_Data");
			if (!Directory.Exists(appDataPath)) {
				Directory.CreateDirectory(appDataPath);
			}
			var id = Guid.NewGuid();
			var filename = id + ".pdf";

			// The SignatureResult object has various methods for writing the signature file to a stream (WriteTo()), local file (WriteToFile()), open
			// a stream to read the content (OpenRead()) and get its contents (GetContent()). For large files, avoid the method GetContent() to avoid
			// memory allocation issues.
			signatureResult.WriteToFile(Path.Combine(appDataPath, filename));

			return View("SignatureInfo", new SignatureInfoModel() {
				File = filename.Replace(".", "_"), // Note: we're passing the filename argument with "." as "_" because of limitations of ASP.NET MVC
				SignerCertificate = signerCert
			});
		}
	}
}

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
                var signatureStarter = new PadesSignatureStarter(Util.GetRestPkiClient()) {

                    // Set the unit of measurement used to edit the pdf marks and visual representations
                    MeasurementUnits = PadesMeasurementUnits.Centimeters,

                    // Set the signature policy
                    SignaturePolicyId = StandardPadesSignaturePolicies.PkiBrazil.BasicWithPkiBrazilCerts,
                    // Note: Depending on the signature policy chosen above, setting the security context below may be mandatory (this is not
                    // the case for ICP-Brasil policies, which will automatically use the PkiBrazil security context if none is passed)

                    // Optionally, set a SecurityContext to be used to determine trust in the certificate chain
                    //SecurityContextId = new Guid("ID OF YOUR CUSTOM SECURITY CONTEXT"),

                    // Set a visual representation for the signature (see function below)
                    VisualRepresentation = getVisualRepresentation(),
                };

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

                // If the user was redirected here by Upload (signature with file uploaded by user), the "userfile" URL argument
                // will contain the filename under the "App_Data" folder. Otherwise (signature with server file), we'll sign a sample
                // document.
                UserFile = Request.QueryString["userfile"];
				if (string.IsNullOrEmpty(UserFile)) {
					// Set the PDF to be signed as a byte array
					signatureStarter.SetPdfToSign(Util.GetSampleDocContent());
				} else {
					// Set the path of the file to be signed
					signatureStarter.SetPdfToSign(Server.MapPath("~/App_Data/" + UserFile));
                }				

				// Call the StartWithWebPki() method, which initiates the signature. This yields the token, a 43-character
				// case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
				// signWithRestPki() method on the Web PKI component (see javascript on the view) and also to complete the signature
				// on the POST action below (this should not be mistaken with the API access token).
				var token = signatureStarter.StartWithWebPki();

				ViewState["Token"] = token;
			}
		}
		
		protected void SubmitButton_Click(object sender, EventArgs e) {

            // Get an instance of the PadesSignatureFinisher2 class, responsible for completing the signature process
            var signatureFinisher = new PadesSignatureFinisher2(Util.GetRestPkiClient()) {

                // Set the token for this signature acquired previously
                Token = (string)ViewState["Token"]
            };

			// Call the Finish() method, which finalizes the signature process and returns an object to access the signed PDF
			var result = signatureFinisher.Finish();

            // At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
            // store the PDF on our mock Storage class.

            // The SignatureResult object has various methods for writing the signature file to a stream (WriteTo()), local file (WriteToFile()), open
            // a stream to read the content (OpenRead()) and get its contents (GetContent()). For large files, avoid the method GetContent() to avoid
            // memory allocation issues.
            string fileId;
			using (var resultStream = result.OpenRead()) {
				fileId = StorageMock.Store(resultStream, ".pdf");
            }
            // If you prefer a simpler approach without streams, simply do:
            //fileId = StorageMock.Store(result.GetContent(), ".pdf");

            // What you do at this point is up to you. For demonstration purposes, we'll render a page with a link to
            // download the signed PDF and with the signer's certificate details.
            this.SignatureFilename = fileId;
			this.SignerCertificate = result.Certificate;
			Server.Transfer("PadesSignatureInfo.aspx");
		}

        /*
         * This method defines the visual representation for each signature.
         */
        private PadesVisualRepresentation getVisualRepresentation() {
            return new PadesVisualRepresentation() {

                // The tags {{name}} and {{national_id}} will be substituted according to the user's certificate
                // 
                //      name        : full name of the signer
                //      national_id : if the certificate is ICP-Brasil, contains the signer's CPF
                //
                // For more information, see: https://github.com/LacunaSoftware/RestPkiSamples/blob/master/PadesTags.md
                Text = new PadesVisualText("Signed by {{name}} ({{national_id}})") {

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
            };
        }
    }
}
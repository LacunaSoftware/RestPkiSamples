using Lacuna.RestPki.Api;
using Lacuna.RestPki.Api.PadesSignature;
using Lacuna.RestPki.Client;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.Services;
using System.Web.UI;
using System.Web.UI.WebControls;

namespace WebForms {
    public partial class BatchSignatureOptimized : System.Web.UI.Page {

        protected List<int> DocumentsIds { get; set; }

        protected void Page_Load(object sender, EventArgs e) {

            if (!IsPostBack) {

                // It is up to your application's business logic to determine which documents will compose the batch
                DocumentsIds = Enumerable.Range(1, 10).ToList(); // from 1 to 10
            }
        }

        /**
         * This web method is called asynchronously from  the batch signature page in order to initiate the signature of each document
         * in the batch.
         */
        [WebMethod]
        public static string Start(int id) {

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

            // Set the PDF to be signed
            signatureStarter.SetPdfToSign(Util.GetBatchDocContent(id));

            // Call the StartWithWebPki() method, which initiates the signature. This yields the token, a 43-character
            // case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
            // signWithRestPki() method on the Web PKI component (see batch-signature-optimized-form.js) and also to complete
            // the signature on the POST action below (this should not be mistaken with the API access token).
            var token = signatureStarter.StartWithWebPki();

            // Send to the javascript the token of the signature process to be used to call Web PKI to perform the signature
            return token;
        }

        /**
         * This web method receives the URL argument "token" from the POST request. We'll call REST PKI to complete the signature.
         */
        [WebMethod]
        public static string Complete(string token) {
            // Get an instance of the PadesSignatureFinisher2 class, responsible for completing the signature process
            var signatureFinisher = new PadesSignatureFinisher2(Util.GetRestPkiClient()) {

                // Set the token for this signature acquired previously
                Token = token
            };

            // Call the Finish() method, which finalizes the signature process and returns an SignatureResult object to access 
            // the signed PDF
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

            // Send to the javascript the signed file's id to be referenced on a download link
            return fileId;
        }

        /*
         * This method defines the visual representation for each signature.
         */
        private static PadesVisualRepresentation getVisualRepresentation() {
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

                    // Optionally, set the horizontal alignment of the text ('Left' or 'Right'), if not set the default is Left
                    HorizontalAlign = PadesTextHorizontalAlign.Left

                },

                // We'll use as background the image in Content/PdfStamp.png
                Image = new PadesVisualImage(Util.GetPdfStampContent(), "image/png") {

                    // Opacity is an integer from 0 to 100 (0 is completely transparent, 100 is completely opaque)
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
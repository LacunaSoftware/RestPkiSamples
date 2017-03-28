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
    public partial class BatchSignature : System.Web.UI.Page {

        // Class used to display each of the batch's documents on the page
        class DocumentItem {
            public int Id { get; set; }
            public string Error { get; set; }
            public string DownloadLink { get; set; }
        }

        // We store the IDs of the batch's documents in the ViewState
        private List<int> _documentIds;
        protected List<int> DocumentIds {
            get {
                if (_documentIds == null) {
                    _documentIds = ViewState["DocumentIds"] as List<int>;
                }
                return _documentIds;
            }
            set {
                ViewState["DocumentIds"] = value;
                _documentIds = value;
            }
        }

        // We store the index of the document currently being signed on the ViewState
        private int? _documentIndex;
        protected int DocumentIndex {
            get {
                if (_documentIndex == null) {
                    _documentIndex = (int)ViewState["DocumentIndex"];
                }
                return _documentIndex.Value;
            }
            set {
                ViewState["DocumentIndex"] = value;
                _documentIndex = value;
            }
        }

        protected void Page_Load(object sender, EventArgs e) {

            if (!IsPostBack) {

                // It is up to your application's business logic to determine which documents will compose the batch
                DocumentIds = Enumerable.Range(1, 10).ToList(); // from 1 to 30

                // Populate the DocumentsListView with the batch documents
                DocumentsListView.DataSource = DocumentIds.ConvertAll(i => new DocumentItem() { Id = i });
                DocumentsListView.DataBind();
            }
        }

        // The button "StartBatchButton" is programmatically clicked by the Javascript on batch-signature-form.js once the
        // user has authorized the usage of his certificate
        protected void StartBatchButton_Click(object sender, EventArgs e) {

            // Hide the certificate select (combo box) and the signature buttons
            SignatureControlsPanel.Visible = false;

            // Start the signature of the first document in the batch
            DocumentIndex = -1;
            startNextSignature();
        }

        // The button "DocSignedButton" is programmatically clicked by the Javascript on batch-signature-form.js when the
        // current document has been signed with the certificate's private key
        protected void DocSignedButton_Click(object sender, EventArgs e) {

            // Complete the signature
            completeSignature();

            // Start the next signature
            startNextSignature();
        }

        private void startNextSignature() {

            // Increment the index of the document currently being signed
            DocumentIndex += 1;

            // Check if we have reached the end of the batch, in which case we fill the hidden field "TokenField" with value "(end)",
            // which signals to the javascript on batch-signature-form.js that the process is completed and the page can be unblocked.
            if (DocumentIndex == DocumentIds.Count) {
                TokenField.Value = "(end)";
                return;
            }

            // Get the ID of the document currently being signed
            var docId = DocumentIds[DocumentIndex];

            string token;

            try {

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

                    // For instance, to use the test certificates on Lacuna Test PKI (for development purposes only!):
                    //SecurityContextId = new Guid("803517ad-3bbc-4169-b085-60053a8f6dbf"),

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

                // Set the PDF to sign
                signatureStarter.SetPdfToSign(Util.GetBatchDocContent(docId));

                // Call the StartWithWebPki() method, which initiates the signature.
                token = signatureStarter.StartWithWebPki();

            } catch (ValidationException ex) {

                // One or more validations failed. We log the error, update the page with a summary of what happened to this document and start the next signature
                //logger.Error(ex, "Validation error starting the signature of a batch document");
                setValidationError(ex.ValidationResults);
                startNextSignature();
                return;

            } catch (Exception ex) {

                // An error has occurred. We log the error, update the page with a summary of what happened to this document and start the next signature
                //logger.Error(ex, "Error starting the signature of a batch document");
                setError(ex.Message);
                startNextSignature();
                return;

            }

            // Send to the javascript the token of the signature process to be used to call Web PKI to perform the signature
            TokenField.Value = token;
        }

        private void completeSignature() {

            string filename;

            try {

                // Get an instance of the PadesSignatureFinisher2 class, responsible for completing the signature process
                var signatureFinisher = new PadesSignatureFinisher2(Util.GetRestPkiClient()) {

                    // Retrieve the token for this signature stored on the initial step (see method startNextSignature())
                    Token = TokenField.Value
                };

                // Call the Finish() method, which finalizes the signature process and returns a SignatureResult object
                var signatureResult = signatureFinisher.Finish();

                // At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
                // store the PDF on the App_Data folder.

                var appDataPath = Server.MapPath("~/App_Data");
                if (!Directory.Exists(appDataPath)) {
                    Directory.CreateDirectory(appDataPath);
                }
                var id = Guid.NewGuid();
                filename = id + ".pdf";

                // The SignatureResult object has various methods for writing the signature file to a stream (WriteTo()), local file (WriteToFile()), open
                // a stream to read the content (OpenRead()) and get its contents (GetContent()). For large files, avoid the method GetContent() to avoid
                // memory allocation issues.
                signatureResult.WriteToFile(Path.Combine(appDataPath, filename));

            } catch (ValidationException ex) {

                // One or more validations failed. We log the error and update the page with a summary of what happened to this document
                // logger.Error(ex, "Validation error completing the signature of a batch document");
                setValidationError(ex.ValidationResults);
                return;

            } catch (Exception ex) {

                // An error has occurred. We log the error and update the page with a summary of what happened to this document
                // logger.Error(ex, "Error completing the signature of a batch document");
                setError(ex.Message);
                return;

            }

            // Update the page with a link to the signed file
            var docItem = DocumentsListView.Items[DocumentIndex];
            docItem.DataItem = new DocumentItem() {
                Id = DocumentIds[DocumentIndex],
                DownloadLink = "Download.aspx?file=" + filename
            };
            docItem.DataBind();
        }

        private void setValidationError(ValidationResults vr) {
            var message = "One or more validations failed: " + string.Join("; ", vr.Errors.Select(e => getDisplayText(e)).ToArray());
            setError(message);
        }

        private string getDisplayText(ValidationItem vi) {
            return string.IsNullOrEmpty(vi.Detail) ? vi.Message : string.Format("{0} ({1})", vi.Message, vi.Detail);
        }

        private void setError(string message) {
            var docItem = DocumentsListView.Items[DocumentIndex];
            docItem.DataItem = new DocumentItem() {
                Id = DocumentIds[DocumentIndex],
                Error = message
            };
            docItem.DataBind();
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

                // We'll use as background the image in Content/images/PdfStamp.png
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
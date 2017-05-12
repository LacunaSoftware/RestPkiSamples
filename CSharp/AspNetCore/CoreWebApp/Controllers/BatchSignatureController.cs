using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Mvc;
using Lacuna.RestPki.Client;
using Lacuna.RestPki.Api;
using Lacuna.RestPki.Api.PadesSignature;
using Microsoft.AspNetCore.Hosting;
using CoreWebApp.Classes;
using Microsoft.Extensions.Options;

namespace CoreWebApp.Controllers {

    [Route("api/[controller]")]
    public class BatchSignatureController : Controller {

        private IHostingEnvironment hostingEnvironment;
        private RestPkiConfig restPkiConfig;

        public BatchSignatureController(IHostingEnvironment hostingEnvironment, IOptions<RestPkiConfig> optionsAccessor) {
            this.hostingEnvironment = hostingEnvironment;
            this.restPkiConfig = optionsAccessor.Value;
        }

        [HttpPost("Start/{id}")]
        public async Task<string> Start(int id) {

            var storage = new Storage(hostingEnvironment);
            var client = Util.GetRestPkiClient(restPkiConfig);

            // Get an instance of the PadesSignatureStarter class, responsible for receiving the signature elements and start the
            // signature process
            var signatureStarter = new PadesSignatureStarter(client) {

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
                    // of signature positioning (valid values are 1-6). Once you decide which is best for your case, you can place 
                    // the code directly here.
                    Position = PadesVisualElements.GetVisualPositioning(client, 1)

                }

            };

            // Set the document to be signed based on its ID (passed to us from the angular controller)
            signatureStarter.SetPdfToSign(storage.GetBatchDocPath(id));

            /*
				Optionally, add marks to the PDF before signing. These differ from the signature visual representation in that
				they are actually changes done to the document prior to signing, not binded to any signature. Therefore, any number
				of marks can be added, for instance one per page, whereas there can only be one visual representation per signature.
				However, since the marks are in reality changes to the PDF, they can only be added to documents which have no previous
				signatures, otherwise such signatures would be made invalid by the changes to the document (see property
				PadesSignatureStarter.BypassMarksIfSigned). This problem does not occurr with signature visual representations.

				We have encapsulated this code in a method to include several possibilities depending on the argument passed.
				Experiment changing the argument to see different examples of PDF marks (valid values are 1-3). Once you decide which 
                is best for your case, you can place the code directly here.
			*/
            //signatureStarter.PdfMarks.Add(PadesVisualElements.GetPdfMark(storage, 1));

            // Call the StartWithWebPkiAsync() method, which initiates the signature. This yields the token, a 43-character
            // case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
            // signWithRestPki() method on the Web PKI component (see javascript on the angular controller) and also to complete
            // the signature on the POST action below (this should not be mistaken with the API access token).
            var token = await signatureStarter.StartWithWebPkiAsync();

            // Return the token obtained from REST PKI
            return token;
        }

        [HttpPost("Complete/{token}")]
        public async Task<string> Complete(string token) {

            var storage = new Storage(hostingEnvironment);
            var client = Util.GetRestPkiClient(restPkiConfig);

            // Get an instance of the PadesSignatureFinisher2 class, responsible for completing the signature process
            var signatureFinisher = new PadesSignatureFinisher2(client) {

                // Set the token for this signature (acquired previously and passed back here by the angular controller)
                Token = token

            };

            // Call the FinishAsync() method, which finalizes the signature process and returns a SignatureResult object
            var signatureResult = await signatureFinisher.FinishAsync();

            // At this point, you'd typically store the signed PDF on a database or storage service. For demonstration purposes, we'll
            // store the PDF on our "storage mock", which in turn stores the PDF on the App_Data folder and render a page with a link
            // to download the signed PDF and with the signer's certificate details.

            // The SignatureResult object has various methods for writing the signature file to a stream (WriteToAsync()), local file (WriteToFileAsync()),
            // open a stream to read the content (OpenReadAsync()) and get its contents (GetContentAsync()). Avoid the method GetContentAsync() to prevent
            // memory allocation issues with large files.
            string filename;
            using (var signatureStream = await signatureResult.OpenReadAsync()) {
                filename = await storage.StoreAsync(signatureStream, ".pdf");
            }

            // Return the filename to be rendered as link to download the signed PDF
            return filename;
        }

    }
}
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Security.Cryptography;
using System.Security.Cryptography.X509Certificates;
using System.Threading.Tasks;
using CoreWebApp.Classes;
using CoreWebApp.Models;
using Lacuna.RestPki.Api;
using Lacuna.RestPki.Api.PadesSignature;
using Lacuna.RestPki.Client;
using Microsoft.AspNetCore.Hosting;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Mvc;
using Microsoft.Extensions.Options;

namespace CoreWebApp.Controllers {

    [Route("api/[controller]")]
    public class PadesSignatureServerKeyController : Controller {

        private IHostingEnvironment hostingEnvironment;
        private RestPkiConfig restPkiConfig;

        public PadesSignatureServerKeyController(IHostingEnvironment hostingEnvironment, IOptions<RestPkiConfig> optionsAcessor) {
            this.hostingEnvironment = hostingEnvironment;
            this.restPkiConfig = optionsAcessor.Value;
        }

        [HttpPost]
        public async Task<SignatureCompleteResponse> Post(string userfile) {

            var storage = new Storage(hostingEnvironment);
            var client = Util.GetRestPkiClient(restPkiConfig);

            // Read the certificate from a PKCS#12 file.
            //var cert = Util.GetSampleCertificateFromPKCS12(storage);

            // Alternative option: Get the certificate from Microsoft CryptoAPI.
            var cert = Util.GetSampleCertificateFromMSCAPI();

            // Get an instance of the PadesSignatureStarter class, responsible for receiving the signature elements and start the
            // signature process
            var signatureStarter = new PadesSignatureStarter(client) {

                // Set the unit of measurement used to edit the pdf marks and visual representations
                MeasurementUnits = PadesMeasurementUnits.Centimeters,

                // Set the signature policy.
                SignaturePolicyId = StandardPadesSignaturePolicies.PkiBrazil.BasicWithPkiBrazilCerts,

                // For this sample, we'll use the Lacuna Test PKI as our security context in order to accept our test certificate used
                // above ("Pierre de Fermat"). This security context should be used ***** FOR DEVELOPMENT PUPOSES ONLY *****
                SecurityContextId = new Guid("803517ad-3bbc-4169-b085-60053a8f6dbf"),


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

            // Set the signer certificate.
            signatureStarter.SetSignerCertificate(cert.RawData);

            // Below we'll either set the PDF file to be signed. Prefer passing a path or a stream instead of the file's contents
            // as a byte array to prevent memory allocation issues with large files.

            // If the "userfile" URL argument is set, it will contain the filename under the "App_Data" folder. Otherwise 
            // (signature with server file), we'll sign a sample document.
            if (string.IsNullOrEmpty(userfile)) {
                signatureStarter.SetPdfToSign(storage.GetSampleDocPath());
            } else {
                Stream userFileStream;
                if (!storage.TryOpenRead(userfile, out userFileStream)) {
                    throw new Exception("File not found");
                }
                signatureStarter.SetPdfToSign(userFileStream);
            }

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

            // Call the Start() method, which initiates the signature. This yields the parameters for the signature using the
            // certificate
            var signatureParams = signatureStarter.Start();

            // Get the key from a PKCS#12 file.
            var pkey = cert.GetRSAPrivateKey();

            // Alternative option: Get the key from Microsoft CryptoAPI.
            // TODO!

            // Perform the signature usign the parameters returned by Rest PKI with the signer's key.
            var signature = pkey.SignHash(signatureParams.ToSignHash, HashAlgorithmName.SHA256, RSASignaturePadding.Pkcs1);

            // Get an instance of the PadesSignatureFinisher2 class, responsible for completing the signature process
            var signatureFinisher = new PadesSignatureFinisher2(client) {

                // Set the token for this signature (acquired previously and passed back here by the angular controller)
                Token = signatureParams.Token,

                // Set the signature
                Signature = signature

            };

            // Call the FinishAsync() method, which finalizes the signature process and returns a SignatureResult object
            var signatureResult = await signatureFinisher.FinishAsync();

            // The "Certificate" property of the SignatureResult object contains information about the certificate used by the user
            // to sign the file.
            var signerCert = signatureResult.Certificate;

            // At this point, you'd typically store the signed PDF on a database or storage service. For demonstration purposes, we'll
            // store the PDF on our "storage mock", which in turn stores the PDF on the App_Data folder.

            // The SignatureResult object has various methods for writing the signature file to a stream (WriteToAsync()), local file (WriteToFileAsync()),
            // open a stream to read the content (OpenReadAsync()) and get its contents (GetContentAsync()). Avoid the method GetContentAsync() to prevent
            // memory allocation issues with large files.
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
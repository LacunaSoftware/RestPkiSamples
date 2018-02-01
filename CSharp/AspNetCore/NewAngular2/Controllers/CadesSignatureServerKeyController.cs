using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Mvc;
using NewAngular2.Classes;
using NewAngular2.Models;
using Microsoft.AspNetCore.Hosting;
using Microsoft.Extensions.Options;
using Lacuna.RestPki.Client;
using Lacuna.RestPki.Api;
using System.IO;
using System.Security.Cryptography.X509Certificates;
using System.Security.Cryptography;

namespace NewAngular2.Controllers {

    [Route("api/[controller]")]
    public class CadesSignatureServerKeyController : Controller {

        private IHostingEnvironment hostingEnvironment;
        private RestPkiConfig restPkiConfig;

        public CadesSignatureServerKeyController(IHostingEnvironment hostingEnvironment, IOptions<RestPkiConfig> optionsAcessor) {
            this.hostingEnvironment = hostingEnvironment;
            this.restPkiConfig = optionsAcessor.Value;
        }

        [HttpPost]
        public async Task<SignatureCompleteResponse> Post(string userfile, string cmsfile) {

            var storage = new Storage(hostingEnvironment);
            var client = Util.GetRestPkiClient(restPkiConfig);

            // Read the certificate from a PKCS#12 file.
            var cert = Util.GetSampleCertificateFromPKCS12(storage);

            // Alternative option: Get the certificate from Microsoft CryptoAPI.
            //var cert = Util.GetSampleCertificateFromMSCAPI();

            // Get an instance of the CadesSignatureStarter class, responsible for receiving the signature elements and start the
            // signature process
            var signatureStarter = new CadesSignatureStarter(client) {

                // Set the signature policy
                SignaturePolicyId = StandardCadesSignaturePolicies.PkiBrazil.AdrBasica,

                // For this sample, we'll use the Lacuna Test PKI as our security context in order to accept our test certificate used
                // above ("Pierre de Fermat"). This security context should be used ***** FOR DEVELOPMENT PUPOSES ONLY *****
                SecurityContextId = new Guid("803517ad-3bbc-4169-b085-60053a8f6dbf"),
            };

            // Set the signer certificate.
            signatureStarter.SetSignerCertificate(cert.RawData);

            // Below we'll either set the file to be signed or the CMS to be co-signed. Prefer passing a path or a stream instead of
            // the file's contents as a byte array to prevent memory allocation issues with large files.

            if (!string.IsNullOrEmpty(userfile)) {

                // If the URL argument "userfile" is filled (signature with file uploaded by user). We'll set the file to be signed
                Stream userFileStream;
                if (!storage.TryOpenRead(userfile, out userFileStream)) {
                    throw new Exception("File not found");
                }
                signatureStarter.SetContentToSign(userFileStream);

            } else if (!string.IsNullOrEmpty(cmsfile)) {

                /*
				 * If the URL argument "cmsfile" is filled, the user has asked to co-sign a previously signed CMS. We'll set the CMS
				 * to be co-signed, which was perviously saved in the App_Data folder by the POST action on this controller. Note two important things:
				 * 
				 * 1. The CMS to be co-signed must be set using the method "SetCmsToCoSign", not the method "SetContentToSign" nor "SetFileToSign"
				 *
				 * 2. Since we're creating CMSs with encapsulated content (see call to SetEncapsulateContent below), we don't need to set the content
				 *    to be signed, REST PKI will get the content from the CMS being co-signed.
				 */
                Stream cmsFileStream;
                if (!storage.TryOpenRead(cmsfile, out cmsFileStream)) {
                    throw new Exception("File not found");
                }
                signatureStarter.SetCmsToCoSign(cmsFileStream);

            } else {

                // If both userfile and cmsfile are null, this is the "signature with server file" case. We'll set the path of the file to be signed
                signatureStarter.SetFileToSign(storage.GetSampleDocPath());

            }

            // Optionally, set whether the content should be encapsulated in the resulting CMS. If this parameter is omitted or set to null, the 
            // following rules apply:
            // - If no CmsToCoSign is given, the resulting CMS will include the content;
            // - If a CmsToCoSign is given, the resulting CMS will include the content if and only if the CmsToCoSign also includes the content.
            signatureStarter.SetEncapsulateContent(true);

            // Call the Start() method, which initiates the signature. This yields the parameters for the signature using the certificate.
            var signatureParams = signatureStarter.Start();

            // Get the key from a PKCS#12 file.
            var pkey = cert.GetRSAPrivateKey();

            // Alternative option: Get the key from Microsoft CryptoAPI.
            // TODO!

            // Perform the signature using the parameters returned by Rest PKI with the signer's key.
            var signature = pkey.SignHash(signatureParams.ToSignHash, HashAlgorithmName.SHA256, RSASignaturePadding.Pkcs1);

            // Get an instance of the CadesSignatureFinisher2 class, responsible for completing the signature process
            var signatureFinisher = new CadesSignatureFinisher2(client) {

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

            // At this point, you'd typically store the signed CMS on a database or storage service. For demonstration purposes, we'll
            // store the CMS on our "storage mock", which in turn stores the CMS on the App_Data folder.

            // The SignatureResult object has various methods for writing the signature file to a stream (WriteToAsync()), local file (WriteToFileAsync()),
            // open a stream to read the content (OpenReadAsync()) and get its contents (GetContentAsync()). Avoid the method GetContentAsync() to prevent
            // memory allocation issues with large files.
            string filename;
            using (var signatureStream = await signatureResult.OpenReadAsync()) {
                filename = await storage.StoreAsync(signatureStream, ".p7s");
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
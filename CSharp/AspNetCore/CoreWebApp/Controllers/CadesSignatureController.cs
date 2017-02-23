using CoreWebApp.Classes;
using CoreWebApp.Models;
using Lacuna.RestPki.Api;
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
	public class CadesSignatureController : Controller {

		private IHostingEnvironment hostingEnvironment;
		private RestPkiConfig restPkiConfig;

		public CadesSignatureController(IHostingEnvironment hostingEnvironment, IOptions<RestPkiConfig> optionsAccessor) {
			this.hostingEnvironment = hostingEnvironment;
			this.restPkiConfig = optionsAccessor.Value;
		}

		[HttpPost]
		public async Task<string> Start(string userfile, string cmsfile) {

			var storage = new Storage(hostingEnvironment);
			var client = Util.GetRestPkiClient(restPkiConfig);

			// Get an instance of the CadesSignatureStarter class, responsible for receiving the signature elements and start the
			// signature process
			var signatureStarter = new CadesSignatureStarter(client) {

				// Set the signature policy
				SignaturePolicyId = StandardCadesSignaturePolicies.PkiBrazil.AdrBasica,

                // Optionally, set a SecurityContext to be used to determine trust in the certificate chain
                //SecurityContextId = StandardSecurityContexts.PkiBrazil,
                // Note: Depending on the signature policy chosen above, setting the security context may be mandatory (this is not
                // the case for ICP-Brasil policies, which will automatically use the PkiBrazil security context if none is passed)

            };

            if (!string.IsNullOrEmpty(userfile)) {

                // If the URL argument "userfile" is filled (signature with file uploaded by user). We'll set the path of the file 
                // to be signed
                byte[] content;
                if (!storage.TryOpenRead(userfile, out content)) {
                    throw new Exception("File not found");
                }
                signatureStarter.SetContentToSign(content);

            } else if (!string.IsNullOrEmpty(cmsfile)) {

                /*
				 * If the URL argument "cmsfile" is filled, the user has asked to co-sign a previously signed CMS. We'll set the path to the CMS
				 * to be co-signed, which was perviously saved in the App_Data folder by the POST action on this controller. Note two important things:
				 * 
				 * 1. The CMS to be co-signed must be set using the method "SetCmsToCoSign", not the method "SetContentToSign" nor "SetFileToSign"
				 *
				 * 2. Since we're creating CMSs with encapsulated content (see call to SetEncapsulateContent below), we don't need to set the content
				 *    to be signed, REST PKI will get the content from the CMS being co-signed.
				 */
                byte[] content;
                if (!storage.TryOpenRead(cmsfile, out content)) {
                    throw new Exception("File not found");
                }
                signatureStarter.SetCmsToCoSign(content);

            } else {

                // If both userfile and cmsfile are null, this is the "signature with server file" case. We'll set the path of the file to be signed
                signatureStarter.SetFileToSign(storage.GetSampleDocPath());

            }

			var token = await signatureStarter.StartWithWebPkiAsync();

			return token;
		}

		[HttpPost("{token}")]
		public async Task<SignatureCompleteResponse> Complete(string token) {

			var storage = new Storage(hostingEnvironment);
			var client = Util.GetRestPkiClient(restPkiConfig);

			// Get an instance of the CadesSignatureFinisher2 class, responsible for completing the signature process
			var signatureFinisher = new CadesSignatureFinisher2(client) {

				// Set the token for this signature (rendered in a hidden input field, see the view)
				Token = token

			};

			// Call the FinishAsync() method, which finalizes the signature process and returns a SignatureResult object
			var signatureResult = await signatureFinisher.FinishAsync();

			// The "Certificate" property of the SignatureResult object contains information about the certificate used by the user
			// to sign the file.
			var signerCert = signatureResult.Certificate;

			// At this point, you'd typically store the signed CMS on a database or storage service. For demonstration purposes, we'll
			// store the CMS on our "storage mock", which in turn stores the CMS on the App_Data folder.

			// The SignatureResult object has various methods for writing the signature file to a stream (WriteTo()), local file (WriteToFile()), open
			// a stream to read the content (OpenRead()) and get its contents (GetContent()). For large files, avoid the method GetContent() to avoid
			// memory allocation issues.
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

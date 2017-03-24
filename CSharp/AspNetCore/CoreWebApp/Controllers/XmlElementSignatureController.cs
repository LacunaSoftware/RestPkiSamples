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
	public class XmlElementSignatureController : Controller {

		private IHostingEnvironment hostingEnvironment;
		private RestPkiConfig restPkiConfig;

		public XmlElementSignatureController(IHostingEnvironment hostingEnvironment, IOptions<RestPkiConfig> optionsAccessor) {
			this.hostingEnvironment = hostingEnvironment;
			this.restPkiConfig = optionsAccessor.Value;
		}

		[HttpPost]
		public async Task<string> Start() {

			var storage = new Storage(hostingEnvironment);
			var client = Util.GetRestPkiClient(restPkiConfig);

			// Get an instance of the XmlElementSignatureStarter class, responsible for receiving the signature elements and start the
			// signature process
			var signatureStarter = new XmlElementSignatureStarter(client) {

				// Set the signature policy
				SignaturePolicyId = StandardXmlSignaturePolicies.PkiBrazil.NFePadraoNacional,
				// Note: Depending on the signature policy chosen above, setting the security context below may be mandatory (this is not
				// the case for ICP-Brasil policies, which will automatically use the PkiBrazil security context if none is passed)

				// Optionally, set a SecurityContext to be used to determine trust in the certificate chain
				//SecurityContextId = new Guid("ID OF YOUR CUSTOM SECURITY CONTEXT"),
				// For instance, to use the test certificates on Lacuna Test PKI (for development purposes only!):
				//SecurityContextId = new Guid("803517ad-3bbc-4169-b085-60053a8f6dbf"),

			};

			// Set the XML to be signed, a sample Brazilian fiscal invoice pre-generated
			signatureStarter.SetXml(storage.GetSampleNFePath());

			// Set the ID of the element to be signed
			signatureStarter.SetToSignElementId("NFe35141214314050000662550010001084271182362300");

			// Call the StartWithWebPkiAsync() method, which initiates the signature. This yields the token, a 43-character
			// case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
			// signWithRestPki() method on the Web PKI component (see javascript on the angular controller) and also to complete
			// the signature on the POST action below (this should not be mistaken with the API access token).
			var token = await signatureStarter.StartWithWebPkiAsync();

			return token;
		}

		[HttpPost("{token}")]
		public async Task<SignatureCompleteResponse> Complete(string token) {

			var storage = new Storage(hostingEnvironment);
			var client = Util.GetRestPkiClient(restPkiConfig);

			// Get an instance of the XmlSignatureFinisher class, responsible for completing the signature process
			var signatureFinisher = new XmlSignatureFinisher(client) {

				// Set the token for this signature (acquired previously and passed back here by the angular controller)
				Token = token

			};

			// Call the FinishAsync() method, which finalizes the signature process and returns a SignatureResult object
			var signedXmlBytes = await signatureFinisher.FinishAsync();

			// The "Certificate" property of the SignatureResult object contains information about the certificate used by the user
			// to sign the file.
			var signerCert = signatureFinisher.GetCertificateInfo();

			// At this point, you'd typically store the signed XML on a database or storage service. For demonstration purposes, we'll
			// store the XML on our "storage mock", which in turn stores the XML on the App_Data folder.

			// The SignatureResult object has various methods for writing the signature file to a stream (WriteToAsync()), local file (WriteToFileAsync()),
			// open a stream to read the content (OpenReadAsync()) and get its contents (GetContentAsync()). Avoid the method GetContentAsync() to prevent
			// memory allocation issues with large files.
			var filename = await storage.StoreAsync(signedXmlBytes, ".xml");

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

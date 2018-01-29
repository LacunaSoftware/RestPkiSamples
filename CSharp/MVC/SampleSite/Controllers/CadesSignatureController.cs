using Lacuna.RestPki.Api;
using Lacuna.RestPki.Client;
using Lacuna.RestPki.SampleSite.Classes;
using Lacuna.RestPki.SampleSite.Models;
using SampleSite.Classes;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Threading.Tasks;
using System.Web;
using System.Web.Mvc;

namespace Lacuna.RestPki.SampleSite.Controllers {

	public class CadesSignatureController : BaseController {

		/**
		 * This action initiates a CAdES signature using REST PKI and renders the signature page.
		 *
		 * All CAdES signature examples converge to this action, but with different URL arguments:
		 *
		 *      1. Signature with a server file               : no arguments filled
		 *      2. Signature with a file uploaded by the user : "userfile" filled
		 *      3. Co-signature of a previously signed CMS    : "cmsfile" filled
         *      
		 */
		[HttpGet]
		public async Task<ActionResult> Index(string userfile, string cmsfile) {

            // Get an instance of the CadesSignatureStarter class, responsible for receiving the signature
            // elements and start the signature process.
			var signatureStarter = new CadesSignatureStarter(Util.GetRestPkiClient()) {

				// Set the signature policy.
				SignaturePolicyId = StandardCadesSignaturePolicies.PkiBrazil.AdrBasica,

                // Set a SecurityContext to be used to determine trust in the certificate chain. We have
                // encapsulated the security context choice on Util.cs.
                SecurityContextId = Util.GetSecurityContextId(),

                // Optionally, set whether the content should be encapsulated in the resulting CMS. If this
                // parameter is ommitted, the following rules apply:
                // - If no CmsToCoSign is given, the resulting CMS will include the content.
                // - If a CmsToCoSign is given, the resulting CMS will include the content if and only if the
                //   CmsToCoSign also includes the content.
                EncapsulateContent = true

			};

			if (!string.IsNullOrEmpty(userfile)) {

                // If the URL argument "userfile" is filled, it means the user was redirected here by
                // UploadController (signature with file uploaded by user). We'll set the path of the file to
                // be signed, which was saved in the App_Data folder by UploadController.
				signatureStarter.SetFileToSign(Server.MapPath("~/App_Data/" + userfile.Replace("_", ".")));

			} else if (!string.IsNullOrEmpty(cmsfile)) {

				/*
				 * If the URL argument "cmsfile" is filled, the user has asked to co-sign a previously signed
                 * CMS. We'll set the path to the CMS to be co-signed, which was perviously saved in the
                 * App_Data folder by the POST action on this controller. Note two important things:
				 * 
				 *      1. The CMS to be co-signed must be set using the method "SetCmsToCoSign", not the
                 *         method "SetContentToSign" nor "SetFileToSign".
				 *
				 *      2. Since we're creating CMSs with encapsulated content (see call to
                 *         SetEncapsulateContent below), we don't need to set the content to be signed,
                 *         REST PKI will get the content from the CMS being co-signed.
				 */
				signatureStarter.SetCmsToCoSign(Server.MapPath("~/App_Data/" + cmsfile.Replace("_", ".")));

			} else {

                // If both userfile and cmsfile are null, this is the "signature with server file" case.
                // We'll set the path of the file to be signed.
				signatureStarter.SetFileToSign(Util.GetSampleDocPath());

			}

            // Call the StartWithWebPki() method, which initiates the signature. This yields the token, a
            // 43-character case-sensitive URL-safe string, which identifies this signature process. We'll
            // use this value to call the signWithRestPki() method on the Web PKI component (see javascript
            // on the view) and also to complete the signature on the POST action below (this should not be
            // mistaken with the API access token).
			var token = await signatureStarter.StartWithWebPkiAsync();

            // The token acquired above can only be used for a single signature attempt. In order to retry
            // the signature it is necessary to get a new token. This can be a problem if the user uses the
            // back button of the browser, since the browser might show a cached page that we rendered
            // previously, with a now stale token. To prevent this from happening, we call the method
            // SetNoCacheHeaders() (in BaseController) which sets HTTP headers to prevent caching of the
            // page.
			base.SetNoCacheHeaders();

			// Render the signature page with the token obtained from REST PKI.
			return View(new CadesSignatureModel() {
				Token = token,
				UserFile = userfile,
				CmsFile = cmsfile
			});
		}

		/**
		 * This action receives the form submission from the view. We'll call REST PKI to complete the
         * signature.
		 */
		[HttpPost]
		public async Task<ActionResult> Index(CadesSignatureModel model) {

            // Get an instance of the CadesSignatureFinisher2 class, responsible for completing the signature
            // process.
			var signatureFinisher = new CadesSignatureFinisher2(Util.GetRestPkiClient()) {

				// Set the token for this signature. (rendered in a hidden input field, see the view)
				Token = model.Token

			};

            // Call the FinishAsync() method, which finalizes the signature process and returns a
            // SignatureResult object.
			var result = await signatureFinisher.FinishAsync();

            // The "Certificate" property of the SignatureResult object contains information about the
            // certificate used by the user to sign the file.
			var signerCert = result.Certificate;

            // At this point, you'd typically store the CMS on your database. For demonstration
            // purposes, we'll store the PDF on our mock Storage class.

            // The SignatureResult object has various methods for writing the signature file to a stream
            // (WriteTo()), local file (WriteToFile()), open a stream to read the content (OpenRead()) and
            // get its contents (GetContent()). For large files, avoid the method GetContent() to avoid
            // memory allocation issues.
            string fileId;
            using (var resultStream = result.OpenRead()) {
                fileId = StorageMock.Store(resultStream, ".p7s");
            }

            // Render the signature information page.
			return View("SignatureInfo", new SignatureInfoModel() {
				File = fileId,
				SignerCertificate = signerCert
			});
		}

	}
}

using Lacuna.RestPki.Api;
using Lacuna.RestPki.Client;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;

namespace WebForms {

	public partial class CadesSignature : System.Web.UI.Page {

		protected string UserFile { get; private set; }
		protected string CmsFile { get; private set; }
		public string SignatureFilename { get; private set; }
		public PKCertificate SignerCertificate { get; private set; }

		protected void Page_Load(object sender, EventArgs e) {

			if (!IsPostBack) {

				// Get an instance of the CadesSignatureStarter class, responsible for receiving the signature elements and start the
				// signature process
				var signatureStarter = Util.GetRestPkiClient().GetCadesSignatureStarter();

				// Set the signature policy
				signatureStarter.SetSignaturePolicy(StandardCadesSignaturePolicies.PkiBrazil.AdrBasica);

				// Set the security context to be used to determine trust in the certificate chain
				signatureStarter.SetSecurityContext(Util.GetSecurityContextId());

				// Optionally, set whether the content should be encapsulated in the resulting CMS. If this parameter is ommitted,
				// the following rules apply:
				// - If no CmsToCoSign is given, the resulting CMS will include the content
				// - If a CmsToCoSign is given, the resulting CMS will include the content if and only if the CmsToCoSign also includes the content
				signatureStarter.SetEncapsulateContent(true);

				UserFile = Request.QueryString["userfile"];
				CmsFile = Request.QueryString["cmsfile"];
				if (!String.IsNullOrEmpty(UserFile)) {

					// If the user was redirected here by Upload (signature with file uploaded by user), the "userfile" URL argument
					// will contain the filename under the "App_Data" folder. 
					signatureStarter.SetFileToSign(Server.MapPath("~/App_Data/" + UserFile));

				} else if (!String.IsNullOrEmpty(CmsFile)) {

					/*
					 * If the URL argument "cmsfile" is filled, the user has asked to co-sign a previously signed CMS. We'll set the path to the CMS
					 * to be co-signed, which was perviously saved in the App_Data folder by the POST action on this controller. Note two important things:
					 * 
					 * 1. The CMS to be co-signed must be set using the method "SetCmsToCoSign", not the method "SetContentToSign" nor "SetFileToSign"
					 *
					 * 2. Since we're creating CMSs with encapsulated content (see call to SetEncapsulateContent above), we don't need to set the content
					 *    to be signed, REST PKI will get the content from the CMS being co-signed.
					 */
					signatureStarter.SetCmsToCoSign(Server.MapPath("~/App_Data/" + CmsFile));

				} else {

					// If both userfile and cmsfile are null, this is the "signature with server file" case. We'll set the path of the file to be signed
					signatureStarter.SetFileToSign(Util.GetSampleDocPath());

				}

				// Call the StartWithWebPki() method, which initiates the signature. This yields the token, a 43-character
				// case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
				// signWithRestPki() method on the Web PKI component (see javascript on the view) and also to complete the signature
				// on the Click-event handler below (this should not be mistaken with the API access token).
				var token = signatureStarter.StartWithWebPki();

				ViewState["Token"] = token;
			}
		}

		protected void SubmitButton_Click(object sender, EventArgs e) {

			// Get an instance of the CadesSignatureFinisher2 class, responsible for completing the signature process
			var signatureFinisher = new CadesSignatureFinisher2(Util.GetRestPkiClient()) {

				// Set the token for this signature acquired previously
				Token = (string)ViewState["Token"]
			};

			// Call the Finish() method, which finalizes the signature process and returns a SignatureResult object
			var result = signatureFinisher.Finish();

			// At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
			// store the PDF on our mock Storage class.

			// The SignatureResult object has various methods for writing the signature file to a stream (WriteTo()), local file (WriteToFile()), open
			// a stream to read the content (OpenRead()) and get its contents (GetContent()). For large files, avoid the method GetContent() to avoid
			// memory allocation issues.
			string fileId;
			using (var resultStream = result.OpenRead()) {
				fileId = StorageMock.Store(resultStream, ".p7s");
			}
			// If you prefer a simpler approach without streams, simply do:
			//fileId = StorageMock.Store(result.GetContent(), ".pdf");

			// What you do at this point is up to you. For demonstration purposes, we'll render a page with a link to
			// download the signed PDF and with the signer's certificate details.
			this.SignatureFilename = fileId;
			this.SignerCertificate = result.Certificate;

			Server.Transfer("CadesSignatureInfo.aspx");
		}
	}
}

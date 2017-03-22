using System;
using System.Collections;
using System.Configuration;
using System.Data;
using System.Linq;
using System.Web;
using System.Web.Security;
using System.Web.UI;
using System.Web.UI.HtmlControls;
using System.Web.UI.WebControls;
using System.Web.UI.WebControls.WebParts;
using System.Xml.Linq;
using Lacuna.RestPki.Client;
using Lacuna.RestPki.Api;
using System.IO;

namespace WebApplication1 {

	public partial class CadesSignature : System.Web.UI.Page {

		public string SignatureFilename { get; private set; }
		public PKCertificate SignerCertificate { get; private set; }

		protected void Page_Load(object sender, EventArgs e) {

			if (!IsPostBack) {

				// Get an instance of the CadesSignatureStarter class, responsible for receiving the signature elements and start the
				// signature process
				var signatureStarter = Util.GetRestPkiClient().GetCadesSignatureStarter();

				// Set the file to be signed as a byte array
				signatureStarter.SetContentToSign(Util.GetSampleDocContent());

				// Set the signature policy
				signatureStarter.SetSignaturePolicy(StandardCadesSignaturePolicies.PkiBrazil.AdrBasica);
				// Note: Depending on the signature policy chosen above, setting the security context below may be mandatory (this is not
				// the case for ICP-Brasil policies, which will automatically use the PkiBrazil security context if none is passed)

				// Optionally, set a SecurityContext to be used to determine trust in the certificate chain
				//signatureStarter.SetSecurityContext(new Guid("ID OF YOUR CUSTOM SECURITY CONTEXT"));

				// For instance, to use the test certificates on Lacuna Test PKI (for development purposes only!):
				//signatureStarter.SetSecurityContext(new Guid("803517ad-3bbc-4169-b085-60053a8f6dbf"));

				// Optionally, set whether the content should be encapsulated in the resulting CMS. If this parameter is ommitted,
				// the following rules apply:
				// - If no CmsToSign is given, the resulting CMS will include the content
				// - If a CmsToCoSign is given, the resulting CMS will include the content if and only if the CmsToCoSign also includes the content
				signatureStarter.SetEncapsulateContent(true);

				// Call the StartWithWebPki() method, which initiates the signature. This yields the token, a 43-character
				// case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
				// signWithRestPki() method on the Web PKI component (see javascript on the view) and also to complete the signature
				// on the POST action below (this should not be mistaken with the API access token).
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
			var signatureResult = signatureFinisher.Finish();

			// The "Certificate" property of the SignatureResult object contains information about the certificate used by the user
			// to sign the file.
			var signerCert = signatureResult.Certificate;

			// At this point, you'd typically store the CMS on your database. For demonstration purposes, we'll
			// store the CMS on the App_Data folder and render a page with a link to download the CMS and with the
			// signer's certificate details.

			var appDataPath = Server.MapPath("~/App_Data");
			if (!Directory.Exists(appDataPath)) {
				Directory.CreateDirectory(appDataPath);
			}
			var id = Guid.NewGuid();
			var filename = id + ".p7s";

			// The SignatureResult object has various methods for writing the signature file to a stream (WriteTo()), local file (WriteToFile()), open
			// a stream to read the content (OpenRead()) and get its contents (GetContent()). For large files, avoid the method GetContent() to avoid
			// memory allocation issues.
			signatureResult.WriteToFile(Path.Combine(appDataPath, filename));

			this.SignatureFilename = filename;
			this.SignerCertificate = signerCert;
			Server.Transfer("CadesSignatureInfo.aspx");
		}
	}
}

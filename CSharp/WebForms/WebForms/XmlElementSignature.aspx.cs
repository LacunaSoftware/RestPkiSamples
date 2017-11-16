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

	public partial class XmlElementSignature : System.Web.UI.Page {

		public string SignatureFilename { get; private set; }
		public PKCertificate SignerCertificate { get; private set; }

		protected void Page_Load(object sender, EventArgs e) {

			if (!IsPostBack) {

				// Get an instance of the XmlElementSignatureStarter class, responsible for receiving the signature elements and start the
				// signature process
				var signatureStarter = new XmlElementSignatureStarter(Util.GetRestPkiClient());

				// Set the XML to be signed, a sample Brazilian fiscal invoice pre-generated
				signatureStarter.SetXml(Util.GetSampleNFeContent());

				// Set the ID of the element to be signed (this ID is present in the invoice above)
				signatureStarter.SetToSignElementId("NFe35141214314050000662550010001084271182362300");

				// Set the signature policy
				signatureStarter.SetSignaturePolicy(StandardXmlSignaturePolicies.PkiBrazil.NFePadraoNacional);

				// Set the security context to be used to determine trust in the certificate chain
				signatureStarter.SetSecurityContext(Util.GetSecurityContextId());

				// Call the StartWithWebPki() method, which initiates the signature. This yields the token, a 43-character
				// case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
				// signWithRestPki() method on the Web PKI component (see javascript on the view) and also to complete the signature
				// on the POST action below (this should not be mistaken with the API access token).
				var token = signatureStarter.StartWithWebPki();

				ViewState["Token"] = token;
			}

		}

		protected void SubmitButton_Click(object sender, EventArgs e) {

			// Get an instance of the XmlSignatureFinisher class, responsible for completing the signature process
			var signatureFinisher = new XmlSignatureFinisher(Util.GetRestPkiClient()) {

				// Set the token for this signature (rendered in a hidden input field, see the view)
				Token = (string)ViewState["Token"]
			};

			// Call the Finish() method, which finalizes the signature process and returns the signed XML
			var signedXml = signatureFinisher.Finish();

			// Get information about the certificate used by the user to sign the file. This method must only be called after
			// calling the Finish() method.
			var signerCert = signatureFinisher.GetCertificateInfo();

			// At this point, you'd typically store the XML on your database. For demonstration purposes, we'll
			// store the XML on the App_Data folder and render a page with a link to download the CMS and with the
			// signer's certificate details.

			var appDataPath = Server.MapPath("~/App_Data");
			if (!Directory.Exists(appDataPath)) {
				Directory.CreateDirectory(appDataPath);
			}
			var id = Guid.NewGuid();
			var filename = id + ".xml";
			File.WriteAllBytes(Path.Combine(appDataPath, filename), signedXml);

			this.SignatureFilename = filename;
			this.SignerCertificate = signerCert;
			Server.Transfer("XmlElementSignatureInfo.aspx");
		}
	}
}
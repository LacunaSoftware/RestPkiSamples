using Lacuna.RestPki.Client;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;

namespace WebForms {
	public partial class PadesSignatureInfo : System.Web.UI.Page {

		protected PKCertificate signerCertificate;
		protected string signatureFilename;

		protected void Page_Load(object sender, EventArgs e) {
			this.signatureFilename = PreviousPage.SignatureFilename;
			this.signerCertificate = PreviousPage.SignerCertificate;
		}
	}
}
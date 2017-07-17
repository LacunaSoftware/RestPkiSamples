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

namespace WebApplication1 {

	public partial class XmlElementSignatureInfo : System.Web.UI.Page {

		protected PKCertificate signerCertificate;
		protected string signatureFilename;

		protected void Page_Load(object sender, EventArgs e) {
			this.signatureFilename = PreviousPage.SignatureFilename;
			this.signerCertificate = PreviousPage.SignerCertificate;
		}
	}
}

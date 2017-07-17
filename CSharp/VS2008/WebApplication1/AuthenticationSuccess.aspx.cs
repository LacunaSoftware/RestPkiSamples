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

	public partial class AuthenticationSuccess : System.Web.UI.Page {

		protected PKCertificate certificate;

		protected void Page_Load(object sender, EventArgs e) {
			this.certificate = PreviousPage.Certificate;
		}
	}
}

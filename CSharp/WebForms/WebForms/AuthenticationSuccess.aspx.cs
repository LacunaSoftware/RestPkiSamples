using Lacuna.RestPki.Client;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;

namespace WebForms {

	public partial class AuthenticationSuccess : System.Web.UI.Page {

		protected PKCertificate certificate;

		protected void Page_Load(object sender, EventArgs e) {
			this.certificate = PreviousPage.Certificate;
		}
	}
}
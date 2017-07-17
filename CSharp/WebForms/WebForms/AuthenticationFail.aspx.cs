using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;

namespace WebForms {

	public partial class AuthenticationFail : System.Web.UI.Page {

		protected string vrHtml;

		protected void Page_Load(object sender, EventArgs e) {
			vrHtml = PreviousPage.ValidationResults.ToString().Replace("\n", "<br/>").Replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;");
		}
	}
}
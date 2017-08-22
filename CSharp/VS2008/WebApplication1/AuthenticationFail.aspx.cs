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

namespace WebApplication1 {

	public partial class AuthenticationFail : System.Web.UI.Page {

		protected string vrHtml;

		protected void Page_Load(object sender, EventArgs e) {
			vrHtml = PreviousPage.ValidationResults.ToString().Replace("\n", "<br/>").Replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;");
		}
	}
}

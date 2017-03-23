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
	public partial class Download : System.Web.UI.Page {
		protected void Page_Load(object sender, EventArgs e) {
			var filename = Request.QueryString["file"].Replace("_", ".");
			var path = Server.MapPath(string.Format("~/App_Data/{0}", filename));
			//Response.ContentType = MimeMapping.GetMimeMapping(filename);
			Response.AddHeader("Content-Disposition", "attachment; filename=" + filename);
			Response.WriteFile(path);
			Response.End();
		}
	}
}

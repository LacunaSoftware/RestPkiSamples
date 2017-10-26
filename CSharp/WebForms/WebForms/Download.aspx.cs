using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;

namespace WebForms {

	public partial class Download : System.Web.UI.Page {

		protected void Page_Load(object sender, EventArgs e) {

			string path = null;
			string filename = null;

			if (!string.IsNullOrEmpty(Request.QueryString["file"])) {

				filename = Request.QueryString["file"];
				var from = Request.QueryString["from"];
				if (from == "content") {
					path = Server.MapPath(string.Format("~/Content/{0}", filename));
				} else {
					path = Server.MapPath(string.Format("~/App_Data/{0}", filename));
				}

			} else if (!string.IsNullOrEmpty(Request.QueryString["docId"])) {

				int docId;
				if (int.TryParse(Request.QueryString["docId"], out docId)) {
					path = Util.GetBatchDocPath(docId);
					filename = string.Format("{0:D2}.pdf", docId);
				}
				
			}

			if (path != null) {
				Response.ContentType = MimeMapping.GetMimeMapping(filename);
				Response.AddHeader("Content-Disposition", "attachment; filename=" + filename);
				Response.WriteFile(path);
			} else {
				Response.StatusCode = 404;
			}

			Response.End();
		}
	}
}
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;

namespace WebForms {

	public partial class Download : System.Web.UI.Page {

		protected void Page_Load(object sender, EventArgs e) {

            if (!string.IsNullOrEmpty(Request.QueryString["fileId"])) {
                
                try {

                    var filename = Request.QueryString["fileId"];
                    using (var fileStream = StorageMock.OpenRead(filename)) {
                        Response.ContentType = MimeMapping.GetMimeMapping(filename);
                        Response.AddHeader("Content-Disposition", "attachment; filename=" + filename);
                        fileStream.CopyTo(Response.OutputStream);
                    }

                } catch(FileNotFoundException ex) {
                    Response.StatusCode = 404;
                }

            } else {

                string path = null;
                string filename = null;

                if (!string.IsNullOrEmpty(Request.QueryString["file"])) {

                    switch (Request.QueryString["file"]) {

                        case "SampleDocument":
                            path = Util.GetSampleDocPath();
                            filename = "SampleDocument.pdf";
                            break;

                        case "SampleNFe":
                            path = Util.GetSampleNFePath();
                            filename = "SampleNFe.xml";
                            break;
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
            }

            Response.End();
		}
	}
}
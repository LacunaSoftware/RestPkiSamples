using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;

namespace WebForms {
	public partial class Upload : System.Web.UI.Page {

		protected void Page_Load(object sender, EventArgs e) {
			if(!IsPostBack) {
				ViewState["ReturnController"] = Request.QueryString["rc"];
			}
		}

		protected void UploadButton_Click(object sender, EventArgs e) {

			// Check that a file was indeed uploaded
			if (userfile.PostedFiles.Count == 0) {
				throw new Exception("No file uploaded");
			}

			var file = userfile.PostedFiles[0];
			var extension = new FileInfo(file.FileName).Extension;

			// Create the App_Data folder if it does not exist
			var appDataPath = Server.MapPath("~/App_Data");
			if (!Directory.Exists(appDataPath)) {
				Directory.CreateDirectory(appDataPath);
			}

			// Generate a unique id
			var id = Guid.NewGuid();

			// Combine the unique id and the original extension
			var filename = string.Format("{0}{1}", id, extension);

			// Save the file to the App_Data folder with the unique filename
			file.SaveAs(Path.Combine(appDataPath, filename));

			// Redirect the user to either CadesSignature or PadesSignature form, passing the name of the file as a URL argument
			Response.Redirect(ViewState["ReturnController"] + "?userfile=" + filename);
		}
	}
}
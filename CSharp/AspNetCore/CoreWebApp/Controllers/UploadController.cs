using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Hosting;
using System.IO;
using CoreWebApp.Classes;

namespace CoreWebApp.Controllers {

	/*
	* This action allows the user to upload a file to be signed. Once the file is uploaded, we save it to the
	* App_Data folder and return the filename.
	*/
	public class UploadController : Controller {

		private IHostingEnvironment hostingEnvironment;

		public UploadController(IHostingEnvironment hostingEnvironment) {
			this.hostingEnvironment = hostingEnvironment;
		}

		[HttpPost("api/Upload")]
		public async Task<string> Upload(IFormFile file) {

			// Check that a file was indeed uploaded
			if (file == null) {
				throw new Exception("No file uploaded");
			}

			var extension = new FileInfo(file.FileName).Extension;

			// Save the file to the App_Data folder with the unique filename
			var storage = new Storage(hostingEnvironment);
			string filename = null;
			using (Stream buffer = file.OpenReadStream()) {
				filename = await storage.StoreAsync(buffer, extension);
			}

			return filename;
		}
	}
}
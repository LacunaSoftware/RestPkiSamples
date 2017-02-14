using CoreWebApp.Classes;
using Microsoft.AspNetCore.Hosting;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.StaticFiles;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Threading.Tasks;

namespace CoreWebApp.Controllers {

	public class DownloadController : Controller {

		private IHostingEnvironment hostingEnvironment;

		public DownloadController(IHostingEnvironment hostingEnvironment) {
			this.hostingEnvironment = hostingEnvironment;
		}

		[HttpGet("download/{id}")]
		public IActionResult Download(string id) {

			if (string.IsNullOrEmpty(id)) {
				return NotFound();
			}

			var storage = new Storage(hostingEnvironment);

			Stream stream;
			string extension;
			if (!storage.TryOpenRead(id, out stream, out extension)) {
				return NotFound();
			}

			string contentType;
			if (!new FileExtensionContentTypeProvider().TryGetContentType("file" + extension, out contentType)) {
				contentType = "application/octet-stream";
			}

			return new FileStreamResult(stream, contentType);
		}
	}
}


using Microsoft.AspNetCore.Hosting;
using Microsoft.AspNetCore.Http;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Threading.Tasks;

namespace CoreWebApp.Classes {

	public class Storage {

		private IHostingEnvironment hostingEnvironment;

		public Storage(IHostingEnvironment hostingEnvironment) {
			this.hostingEnvironment = hostingEnvironment;
		}

		protected string AppDataPath {
			get {
				return Path.Combine(hostingEnvironment.ContentRootPath, "App_Data");
			}
		}

		public string GetSampleDocPath() {
			return Path.Combine(AppDataPath, "SampleDocument.pdf");
		}

		public string GetSampleNFePath() {
			return Path.Combine(AppDataPath, "SampleNFe.xml");
		}

		public byte[] GetPdfStampContent() {
			return File.ReadAllBytes(Path.Combine(AppDataPath, "PdfStamp.png"));
		}

		public async Task<string> StoreAsync(byte[] content, string extension = "") {
			using (var buffer = new MemoryStream(content)) {
				return await StoreAsync(buffer, extension);
			}
		}

		public async Task<string> StoreAsync(Stream stream, string extension = "") {
			var filename = Guid.NewGuid() + extension;
			var path = Path.Combine(AppDataPath, filename);
			using (var fileStream = File.Create(path)) {
				await stream.CopyToAsync(fileStream);
			}
			return filename.Replace('.', '_');
		}

		public bool TryOpenRead(string fileId, out Stream stream) {
			string extension;
			return TryOpenRead(fileId, out stream, out extension);
		}

		public bool TryOpenRead(string fileId, out Stream stream, out string extension) {
			var filename = fileId.Replace('_', '.');
			var path = Path.Combine(AppDataPath, filename);
			var fileInfo = new FileInfo(path);
			if (!fileInfo.Exists) {
				stream = null;
				extension = null;
				return false;
			}
			extension = fileInfo.Extension;
			stream = File.OpenRead(path);
			return true;
		}
	}
}

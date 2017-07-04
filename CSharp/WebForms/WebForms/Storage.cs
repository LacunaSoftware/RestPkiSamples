using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Web;
using System.Web.SessionState;

namespace WebForms {

	public static class Storage {

		public static string Store(Stream stream, string extension = "") {
			var appDataPath = HttpContext.Current.Server.MapPath("~/App_Data");
			if (!Directory.Exists(appDataPath)) {
				Directory.CreateDirectory(appDataPath);
			}
			var filename = Guid.NewGuid() + extension;
			var path = Path.Combine(appDataPath, filename);
			using (var fileStream = File.Create(path)) {
				stream.CopyTo(fileStream);
			}
			return filename.Replace(".", "_");
		}

		public static string Store(byte[] content, string extension = "") {
			using (var buffer = new MemoryStream(content)) {
				return Store(buffer, extension);
			}
		}

		public static Stream OpenRead(string fileId) {
			string extension;
			return OpenRead(fileId, out extension);
		}

		public static Stream OpenRead(string fileId, out string extension) {
			if (fileId == null) {
				throw new ArgumentNullException("fileId");
			}
			var path = HttpContext.Current.Server.MapPath("~/App_Data/" + fileId.Replace("_", "."));
			var fileInfo = new FileInfo(path);
			if (!fileInfo.Exists) {
				throw new FileNotFoundException("File not found: " + fileId);
			}
			extension = fileInfo.Extension;
			return fileInfo.OpenRead();
		}

		public static byte[] Read(string fileId) {
			string extension;
			return Read(fileId, out extension);
		}

		public static byte[] Read(string fileId, out string extension) {
			using (var inputStream = OpenRead(fileId, out extension)) {
				using (var buffer = new MemoryStream()) {
					inputStream.CopyTo(buffer);
					return buffer.ToArray();
				}
			}
		}

		/// <summary>
		/// Returns the verification code associated with the given document, or null if no verification code has been associated with it
		/// </summary>
		public static string GetVerificationCode(string fileId) {
			// This should be implemented on your application as a SELECT on your "document table" by the
			// ID of the document, returning the value of the verification code column
			return HttpContext.Current.Session[string.Format("Files/{0}/Code", fileId)] as string;
		}

		/// <summary>
		/// Registers the verification code for a given document.
		/// </summary>
		public static void SetVerificationCode(string fileId, string code) {
			// This should be implemented on your application as an UPDATE on your "document table" filling
			// the verification code column, which should be an indexed column
			HttpContext.Current.Session[string.Format("Files/{0}/Code", fileId)] = code;
			HttpContext.Current.Session[string.Format("Codes/{0}", code)] = fileId;
		}

		/// <summary>
		/// Returns the ID of the document associated with a given verification code, or null if no document matches the given code
		/// </summary>
		public static string LookupVerificationCode(string code) {
			if (string.IsNullOrEmpty(code)) {
				return null;
			}
			// This should be implemented on your application as a SELECT on your "document table" by the
			// verification code column, which should be an indexed column
			return HttpContext.Current.Session[string.Format("Codes/{0}", code)] as string;
		}
	}
}
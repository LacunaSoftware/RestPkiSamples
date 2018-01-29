using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Web;

namespace Lacuna.RestPki.SampleSite.Classes {
    public static class StorageMock {

        public static string AppDataPath {
            get {
                return HttpContext.Current.Server.MapPath("~/App_Data");
            }
        }

        public static string Store(Stream stream, string extension = "") {

            if (!Directory.Exists(AppDataPath)) {
                Directory.CreateDirectory(AppDataPath);
            }

            var filename = Guid.NewGuid() + extension;
            var path = Path.Combine(AppDataPath, filename);
            using (var fileStream = File.Create(path)) {
                stream.CopyTo(fileStream);
            }

            return filename.Replace(".", "_");
            // Note: we're passing the filename argument with "." as "_" because of limitations of
            // ASP.NET MVC.
        }

        public static string Store(byte[] content, string extension = "") {
            string fileId;
            using (var stream = new MemoryStream(content)) {
                fileId = Store(stream, extension);
            }
            return fileId;
        }

        public static Stream OpenRead(string filename) {
            string extension;
            return OpenRead(filename, out extension);
        }

        public static Stream OpenRead(string filename, out string extension) {

            if (filename == null) {
                throw new ArgumentNullException("fileId");
            }

            var path = Path.Combine(AppDataPath, filename);
            var fileInfo = new FileInfo(path);
            if (!fileInfo.Exists) {
                throw new FileNotFoundException("File not found: " + filename);
            }
            extension = fileInfo.Extension;
            return fileInfo.OpenRead();
        }

        public static byte[] Read(string fileId) {
            string extension;
            return Read(fileId, out extension);
        }

        public static byte[] Read(string fileId, out string extension) {

            var filename = fileId.Replace("_", ".");
            // Note: we're receiving the fileId argument with "_" as "." because of limitations of
            // ASP.NET MVC.

            using (var inputStream = OpenRead(filename, out extension)) {
                using (var buffer = new MemoryStream()) {
                    inputStream.CopyTo(buffer);
                    return buffer.ToArray();
                }
            }
        }

        internal static string Store(object indexStream, string v) {
            throw new NotImplementedException();
        }
    }
}
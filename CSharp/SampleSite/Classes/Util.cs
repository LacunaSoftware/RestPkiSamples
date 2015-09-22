using Lacuna.RestPki.Client;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Web;

namespace SampleSite.Classes {

	public class Util {

		// -------------------------------------------------------------------------------------------
		private const string restPkiAccessToken = "PASTE YOUR ACCESS TOKEN HERE";
		// -------------------------------------------------------------------------------------------

		public static RestPkiClient GetRestPkiClient() {
			return new RestPkiClient("https://restpki.lacunasoftware.com/", restPkiAccessToken);
		}

		public static Guid SecurityContextId {
			get {
				/*
				 * By changing the argument below, you can accept signatures only signed with certificates
				 * from a certain PKI, for instance, ICP-Brasil (StandardSecurityContexts.PkiBrazil) or Italy's PKI (StandardSecurityContexts.PkiItaly).
				 */
				return Lacuna.RestPki.Api.StandardSecurityContexts.PkiBrazil;
				/*
				 * You can also define a custom security context on the REST PKI website accepting whatever
				 * root certification authorities you desire and then reference that context by its ID.
				 */
				//return new Guid("id-of-your-custom-security-context");
			}
		}

		public static string ContentPath {
			get {
				return HttpContext.Current.Server.MapPath("~/Content");
			}
		}

		public static byte[] GetPdfStampContent() {
			return File.ReadAllBytes(Path.Combine(ContentPath, "PdfStamp.png"));
		}

		public static byte[] GetSampleDocContent() {
         return File.ReadAllBytes(Path.Combine(ContentPath, "SampleDocument.pdf"));
      }

	}
}

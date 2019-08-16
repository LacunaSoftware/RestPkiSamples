using Lacuna.RestPki.Api;
using Lacuna.RestPki.Client;
using System;
using System.Collections;
using System.Collections.Generic;
using System.Configuration;
using System.IO;
using System.Linq;
using System.Security.Cryptography;
using System.Text.RegularExpressions;
using System.Web;

namespace WebForms {

	public static class Util {

		public static RestPkiClient GetRestPkiClient() {
			var accessToken = ConfigurationManager.AppSettings["RestPkiAccessToken"];
			if (string.IsNullOrEmpty(accessToken) || accessToken.Contains(" API ")) {
				throw new Exception("The API access token was not set! Hint: to run this sample you must generate an API access token on the REST PKI website and paste it on the web.config file");
			}
			var endpoint = ConfigurationManager.AppSettings["RestPkiEndpoint"];
			if (string.IsNullOrEmpty(endpoint)) {
				endpoint = "https://pki.rest/";
			}
			return new RestPkiClient(endpoint, accessToken);
		}

		/*
		 * This method is called by all pages to determine the security context to be used.
		 * 
		 * Security contexts dictate which root certification authorities are trusted during
		 * certificate validation. In your API calls, you can use one of the standard security
		 * contexts or reference one of your custom contexts.
		 */
		public static Guid GetSecurityContextId() {
#if DEBUG
			/*
			 * Lacuna Test PKI (for development purposes only!)
			 * 
			 * This security context trusts ICP-Brasil certificates as well as certificates on
			 * Lacuna Software's test PKI. Use it to accept the test certificates provided by
			 * Lacuna Software.
			 * 
			 * THIS SHOULD NEVER BE USED ON A PRODUCTION ENVIRONMENT!
			 */
			return StandardSecurityContexts.LacunaTest;
			// Notice for On Premises users: this security context might not exist on your installation,
			// if you encounter an error please contact developer support.
#else
			// In production, accepting only certificates from ICP-Brasil
			return Lacuna.RestPki.Api.StandardSecurityContexts.PkiBrazil;
#endif
		}

		public static string ContentPath {
			get {
				return HttpContext.Current.Server.MapPath("~/Content");
			}
		}

		public static byte[] GetPdfStampContent() {
			return File.ReadAllBytes(Path.Combine(ContentPath, "PdfStamp.png"));
		}

		public static byte[] GetIcpBrasilLogoContent() {
			return File.ReadAllBytes(Path.Combine(ContentPath, "icp-brasil.png"));
		}

		public static byte[] GetValidationResultIcon(bool isValid) {
			var filename = isValid ? "ok.png" : "not-ok.png";
			return File.ReadAllBytes(Path.Combine(ContentPath, filename));
		}

		public static byte[] GetSampleDocContent() {
			return File.ReadAllBytes(Path.Combine(ContentPath, "SampleDocument.pdf"));
		}

		public static string GetSampleDocPath() {
			return Path.Combine(ContentPath, "SampleDocument.pdf");
		}

		public static string GetBatchDocPath(int id) {
			return Path.Combine(ContentPath, string.Format("{0:D2}.pdf", id % 10));
		}

		public static byte[] GetBatchDocContent(int id) {
			return File.ReadAllBytes(GetBatchDocPath(id));
		}

		public static byte[] GetSampleNFeContent() {
			return File.ReadAllBytes(Path.Combine(ContentPath, "SampleNFe.xml"));
		}

        public static string GetSampleNFePath() {
            return Path.Combine(ContentPath, "SampleNFe.xml");
        }

		public static byte[] GetSampleXmlDocument() {
			return File.ReadAllBytes(Path.Combine(ContentPath, "SampleDocument.xml"));
		}

		public static string JoinStringsPt(IEnumerable<string> strings) {
			var text = new System.Text.StringBuilder();
			var count = strings.Count();
			var index = 0;
			foreach (var s in strings) {
				if (index > 0) {
					if (index < count - 1) {
						text.Append(", ");
					} else {
						text.Append(" e ");
					}
				}
				text.Append(s);
				++index;
			}
			return text.ToString();
		}

	}
}

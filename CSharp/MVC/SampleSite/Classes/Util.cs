using Lacuna.RestPki.Api;
using Lacuna.RestPki.Client;
using System;
using System.Collections.Generic;
using System.Configuration;
using System.IO;
using System.Linq;
using System.Web;

namespace SampleSite.Classes {

	public class Util {

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

		public static string ContentPath {
			get {
				return HttpContext.Current.Server.MapPath("~/Content");
			}
		}

		public static byte[] GetPdfStampContent() {
			return File.ReadAllBytes(Path.Combine(ContentPath, "PdfStamp.png"));
		}

		public static string GetSampleDocPath() {
			return Path.Combine(ContentPath, "01.pdf");
		}

		public static string GetBatchDocPath(int id) {
			return Path.Combine(ContentPath, string.Format("{0:D2}.pdf", id % 10));
		}

		public static string GetSampleNFePath() {
			return Path.Combine(ContentPath, "SampleNFe.xml");
		}

		public static string GetSampleXmlDocumentPath() {
			return Path.Combine(ContentPath, "SampleDocument.xml");
		}

		public static string GetXmlInvoiceWithSigsPath() {
			return Path.Combine(ContentPath, "InvoiceWithSigs.xml");
		}

		public static string GetSamplePeerDocumentPath() {
			return Path.Combine(ContentPath, "SamplePeerDocument.xml");
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
    }
}

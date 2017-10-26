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
			return new Guid("803517ad-3bbc-4169-b085-60053a8f6dbf");
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

		/*
		 * ------------------------------------
		 * Configuration of the code generation
		 * 
		 * - CodeSize   : size of the code in characters
		 * - CodeGroups : number of groups to separate the code (must be a proper divisor of the code size)
		 * 
		 * Examples
		 * --------
		 * 
		 * - CodeSize = 12, CodeGroups = 3 : XXXX-XXXX-XXXX
		 * - CodeSize = 12, CodeGroups = 4 : XXX-XXX-XXX-XXX
		 * - CodeSize = 16, CodeGroups = 4 : XXXX-XXXX-XXXX-XXXX
		 * - CodeSize = 20, CodeGroups = 4 : XXXXX-XXXXX-XXXXX-XXXXX
		 * - CodeSize = 20, CodeGroups = 5 : XXXX-XXXX-XXXX-XXXX-XXXX
		 * - CodeSize = 25, CodeGroups = 5 : XXXXX-XXXXX-XXXXX-XXXXX-XXXXX
		 * 
		 * Entropy
		 * -------
		 * 
		 * The resulting entropy of the code in bits is the size of the code times 5. Here are some suggestions:
		 * 
		 * - 12 characters = 60 bits
		 * - 16 characters = 80 bits
		 * - 20 characters = 100 bits
		 * - 25 characters = 125 bits
		 */
		private const int VerificationCodeSize = 16;
		private const int VerificationCodeGroups = 4;

		// This method generates a verification code, without dashes
		public static string GenerateVerificationCode() {
			// String with exactly 32 letters and numbers to be used on the codes. We recommend leaving this value as is.
			const string Alphabet = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
			// Allocate a byte array large enough to receive the necessary entropy
			var bytes = new byte[(int)Math.Ceiling(VerificationCodeSize * 5 / 8.0)];
			// Generate the entropy with a cryptographic number generator
			using (var rng = RandomNumberGenerator.Create()) {
				rng.GetBytes(bytes);
			}
			// Convert random bytes into bits
			var bits = new BitArray(bytes);
			// Iterate bits 5-by-5 converting into characters in our alphabet
			var sb = new System.Text.StringBuilder();
			for (int i = 0; i < VerificationCodeSize; i++) {
				int n = (bits[i] ? 1 : 0) << 4
					| (bits[i + 1] ? 1 : 0) << 3
					| (bits[i + 2] ? 1 : 0) << 2
					| (bits[i + 3] ? 1 : 0) << 1
					| (bits[i + 4] ? 1 : 0);
				sb.Append(Alphabet[n]);
			}
			return sb.ToString();
		}

		public static string FormatVerificationCode(string code) {
			// Return the code separated in groups
			var charsPerGroup = VerificationCodeSize / VerificationCodeGroups;
			return string.Join("-", Enumerable.Range(0, VerificationCodeGroups).Select(g => code.Substring(g * charsPerGroup, charsPerGroup)));
		}

		public static string ParseVerificationCode(string formattedCode) {
			if (string.IsNullOrEmpty(formattedCode)) {
				return formattedCode;
			}
			return Regex.Replace(formattedCode, "[^A-Za-z0-9]", "");
		}

	}
}

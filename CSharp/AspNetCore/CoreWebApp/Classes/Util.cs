using Lacuna.RestPki.Client;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Security.Cryptography.X509Certificates;
using System.Threading.Tasks;

namespace CoreWebApp.Classes {

    public static class Util {

        private const string CertificateThumbprint = "f6c24db85cb0187c73014cc3834e5a96b8c458bc";

        public static RestPkiClient GetRestPkiClient(RestPkiConfig config) {
            return new RestPkiClient(!string.IsNullOrEmpty(config.Endpoint) ? config.Endpoint : "https://pki.rest/", config.AccessToken);
        }

        public static X509Certificate2 GetSampleCertificateFromPKCS12(Storage storage) {
            return new X509Certificate2(storage.GetSampleCertificateContent(), "1234");
        }

        public static X509Certificate2 GetSampleCertificateFromMSCAPI() {

            X509Certificate2 cert = null;

            using (var store = new X509Store(StoreName.My, StoreLocation.CurrentUser)) {
                store.Open(OpenFlags.ReadOnly);
                foreach (var c in store.Certificates) {
                    if (c.Thumbprint.ToLower() == CertificateThumbprint) {
                        cert = c;
                        break;
                    }
                }
            }

            if (cert == null) {
                throw new InvalidOperationException("...");
            }

            return cert;
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

    }
}

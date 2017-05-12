using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using Microsoft.AspNetCore.Mvc;
using CoreWebApp.Classes;
using Microsoft.AspNetCore.Hosting;
using Microsoft.Extensions.Options;
using Lacuna.RestPki.Api;
using Lacuna.RestPki.Client;
using CoreWebApp.Models;

namespace CoreWebApp.Controllers {

    [Route("api/[controller]")]
    public class OpenXmlSignatureController : Controller {

        private IHostingEnvironment hostingEnvironment;
        private RestPkiConfig restPkiConfig;

        public OpenXmlSignatureController(IHostingEnvironment hostingEnvironment, IOptions<RestPkiConfig> optionsAccessor) {
            this.hostingEnvironment = hostingEnvironment;
            this.restPkiConfig = optionsAccessor.Value;
        }

        [HttpGet("{userfile}")]
        public async Task<OpenXmlSignatureResponse> Get(string userfile) {

            var storage = new Storage(hostingEnvironment);
            var client = Util.GetRestPkiClient(restPkiConfig);

            // Our action only works if a userfile is given to work with.
            if (string.IsNullOrEmpty(userfile)) {
                throw new Exception("No file provided");
            }

            // Get an instance of the XmlSignatureExplorer class, used to open/validate XML signatures.
            var sigExplorer = new XmlSignatureExplorer(client) {
                Validate = true // Specify that we want to validate the signatures in the file, not only inspect them.
            };

            // Set the XML file
            byte[] content;
            if (!storage.TryOpenRead(userfile, out content)) {
                throw new Exception("File not found");
            }
            sigExplorer.SetSignatureFile(content);

            // Parameters for the signature validation. We have encapsulated this code in a method to include several
            // possibilities depending on the argument passed. Experiment changing the argument to see different validation
            // configurations (valid values are 1-2). Once you decide which is best for your case, you can place the code directly here.
            setValidationParameters(sigExplorer, 1);
            // try changing this number ---------^ for different validation parameters.

            // Call the OpenAsync() method, which return the signature file's information.
            var signatures = await sigExplorer.OpenAsync();

            // Render the information. (see file wwwroot/views/open-xml-signature.html for more information on the information returned)
            return new OpenXmlSignatureResponse(signatures);
        }

        // This function is called by the Index method (see above). It contains examples of signature validation parameters.
        private static void setValidationParameters(XmlSignatureExplorer sigExplorer, int caseNumber) {

            switch (caseNumber) {

                /*
					Example #1: accept any valid XmlDSig signature as long as the signer has an ICP-Brasil certificate.

					These parameters will only accept signatures made with ICP-Brasil certificates that comply with the
					minimal security features defined in the XmlDSig standard. The signatures need not, however, follow
					the extra requirements defined in the ICP-Brasil signature policy documentation (DOC-ICP-15.03).
				 */
                case 1:
                    // By omitting the accepted policies catalog and defining a default policy, we're telling Rest PKI to validate
                    // all signatures in the file with the default policy -- even signatures with an explicit signature policy.
                    sigExplorer.AcceptableExplicitPolicies = null;
                    sigExplorer.DefaultSignaturePolicyId = StandardXmlSignaturePolicies.XmlDSigBasic;
                    // The XmlDSigBasic policy requires us to choose a security context.
                    sigExplorer.SecurityContextId = StandardSecurityContexts.PkiBrazil;
                    break;


                /*
					Example #2: accept any valid XmlDSig signature as long as the signer is trusted by Windows.

					Same case as example #1, but using the WindowsServer trust arbitrator.
				 */
                case 2:
                    sigExplorer.AcceptableExplicitPolicies = null;
                    sigExplorer.DefaultSignaturePolicyId = StandardXmlSignaturePolicies.XmlDSigBasic;
                    sigExplorer.SecurityContextId = StandardSecurityContexts.WindowsServer;
                    break;
            }
        }
    }
}
using Lacuna.RestPki.Api;
using Lacuna.RestPki.Client;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;

namespace WebForms {

	/**
	 * This form responds the verification link on the "printer-friendly version" PDF (see PrinterFriendlyVersion.aspx).
	 * 
	 * It is meant to be accessible anonymously (without requiring authentication). Because of this, the document is not
	 * identified by its ID, which is potentially easy to guess, but by a "verification code" generated with a cryptographic
	 * random number generator (see method Util.GenerateVerificationCode). We also take extra steps to deal with potential
	 * brute-force attacks (see below).
	 */
	public partial class Check : System.Web.UI.Page {

		// Properties used by the aspx to render the page (we'll fill these values on Page_Load)
		protected string FileId { get; private set; }
		protected Lacuna.RestPki.Client.PadesSignature Model { get; private set; }

		protected void Page_Load(object sender, EventArgs e) {

			// Get verification code from query string
			var formattedVerificationCode = Request.QueryString["code"];

			// On PrinterFriendlyVersion.aspx, we stored the unformatted version of the verification code (without hyphens) but
			// used the formatted version (with hyphens) on the printer-friendly PDF. Now, we remove the hyphens before looking it up.
			var verificationCode = Util.ParseVerificationCode(formattedVerificationCode);

			// Get document associated with verification code
			var fileId = Storage.LookupVerificationCode(verificationCode);
			if (fileId == null) {
				// Invalid code given!
				// Small delay to slow down brute-force attacks (if you want to be extra careful you might want to add a CAPTCHA to the process)
				Thread.Sleep(TimeSpan.FromSeconds(2));
				// Return Not Found
				Response.StatusCode = 404;
				Response.End();
				return;
			}

			// Read document from storage
			var fileContent = Storage.Read(fileId);

			// Open and validate signatures with Rest PKI
			var client = Util.GetRestPkiClient();
			var sigExplorer = new PadesSignatureExplorer(client) {
				Validate = true,
				DefaultSignaturePolicyId = StandardPadesSignaturePolicies.Basic,
				SecurityContextId = StandardSecurityContexts.PkiBrazil,
			};
			sigExplorer.SetSignatureFile(fileContent);
			var signature = sigExplorer.Open();

			// Set properties for rendering on page (see aspx file)
			this.FileId = fileId;
			this.Model = signature;
		}
	}
}

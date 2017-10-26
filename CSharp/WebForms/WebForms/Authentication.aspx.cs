using Lacuna.RestPki.Api;
using Lacuna.RestPki.Client;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Web;
using System.Web.Security;
using System.Web.UI;
using System.Web.UI.WebControls;

namespace WebForms {

	public partial class Authentication : System.Web.UI.Page {

		public ValidationResults ValidationResults { get; set; }
		public PKCertificate Certificate { get; private set; }

		protected void Page_Load(object sender, EventArgs e) {

			if (!IsPostBack) {

				// Get an instance of the Authentication class
				var auth = Util.GetRestPkiClient().GetAuthentication();

				// Call the StartWithWebPki() method, which initiates the authentication. This yields the "token", a 22-character
				// case-sensitive URL-safe string, which represents this authentication process. We'll use this value to call the
				// signWithRestPki() method on the Web PKI component (see signature-form.js) and also to call the
				// CompleteWithWebPki() method on the Click-event handler below (this should not be mistaken with the API access token).
				var token = auth.StartWithWebPki(Util.GetSecurityContextId());

				// We'll need the token later, so we'll put it on ViewState and we'll render a hidden field on the page with it
				ViewState["Token"] = token;
			}
		}

		protected void SubmitButton_Click(object sender, EventArgs e) {

			// Get the token for this authentication process from ViewState (rendered in a hidden input field, see the view)
			var token = (string)ViewState["Token"];

			// Get an instance of the Authentication class
			var auth = Util.GetRestPkiClient().GetAuthentication();

			// Call the CompleteWithWebPki() method with the token, which finalizes the authentication process. The call yields a
			// ValidationResults which denotes whether the authentication was successful or not.
			var validationResults = auth.CompleteWithWebPki(token);

			// Check the authentication result
			if (!validationResults.IsValid) {
				// If the authentication was not successful, we render a page showing what went wrong
				this.ValidationResults = validationResults;
				Server.Transfer("AuthenticationFail.aspx");
				return;
			}

			// At this point, you have assurance that the certificate is valid according to the TrustArbitrator you
			// selected when starting the authentication and that the user is indeed the certificate's subject. Now,
			// you'd typically query your database for a user that matches one of the certificate's fields, such as
			// userCert.EmailAddress or userCert.PkiBrazil.CPF (the actual field to be used as key depends on your
			// application's business logic) and set the user ID on the auth cookie. For demonstration purposes,
			// we'll just render a page showing some of the user's certificate information.
			this.Certificate = auth.GetCertificate();
			Server.Transfer("AuthenticationSuccess.aspx");
		}
	}
}

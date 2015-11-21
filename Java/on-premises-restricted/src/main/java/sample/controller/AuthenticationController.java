package sample.controller;

import com.lacunasoftware.restpki.*;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.*;
import sample.util.*;

import javax.servlet.http.HttpServletResponse;

@Controller
public class AuthenticationController {

	/*
	 * This action initiates an authentication with REST PKI and renders the authentication page.
	 */
	@RequestMapping(value = "/authentication", method = {RequestMethod.GET})
	public String get(Model model, HttpServletResponse response) throws RestException {

		// Instantiate the Authentication class. For more information, see:
		// https://pki.rest/Content/docs/java-client/index.html?com/lacunasoftware/restpki/Authentication.html
		Authentication auth = new Authentication(Util.getRestPkiClient());

		// Call the Authentication.start() method, which initiates the authentication. This yields the nonce,
		// a 16-byte-array encoded in standard Base64 (24-character case-sensitive string, NOT url-safe) which must be
		// signed using the user certificate's private key (for this we'll use the signData method of the Web PKI
		// component).
		String nonce = auth.start();

		// The nonce acquired above can only be used for a single authentication attempt. In order to retry the signature
		// it is necessary to get a new nonce. This can be a problem if the user uses the back button of the browser,
		// since the browser might show a cached page that we rendered previously, with a now stale nonce. To prevent this
		// from happening, we call the method Util.setNoCacheHeaders(), which sets HTTP headers to prevent caching of the
		// page.
		Util.setNoCacheHeaders(response);

		// Render the authentication page (templates/authentication.html)
		model.addAttribute("nonce", nonce);
		return "authentication";
	}

	/*
	 * This action receives the form submission from the page. We'll call REST PKI to validate the authentication.
	 */
	@RequestMapping(value = "/authentication", method = {RequestMethod.POST})
	public String post(
		@RequestParam(value = "nonce", required = true) String nonce,
		@RequestParam(value = "certificate", required = true) String certificate,
		@RequestParam(value = "signature", required = true) String signature,
	   Model model
	) throws RestException {

		// Instantiate the Authentication class
		Authentication auth = new Authentication(Util.getRestPkiClient());

		// Call the complete() method, which finalizes the authentication process. It receives as input:
		// - The nonce which was signed using the user's certificate
		// - The user's certificate encoding
		// - The nonce signature
		// - A security context, which controls the validation
		// The call yields a ValidationResults which denotes whether the authentication was successful or not.

		// Note on encodings: the nonce, certificate and signature are byte arrays encoded as Base64 strings, which is
		// exactly the format that the Web PKI component outputs (see the file js/authentication.js for more
		// information)

		ValidationResults vr = auth.complete(nonce, certificate, signature, SecurityContext.pkiBrazil);
		// >>> NOTE: By changing the SecurityContext above you can accept only certificates from a certain PKI,
		// for instance ICP-Brasil (SecurityContext.pkiBrazil). You can also define a custom security context on the
		// REST PKI website accepting whatever root certification authorities you wish and reference it here by its ID.

		// Check the authentication result. If it is not successful, we render a page showing what went wrong
		if (!vr.isValid()) {
			model.addAttribute("title", "Authentication failed");
			// The toString() method of the ValidationResults object can be used to obtain the checks performed, but the
			// string contains tabs and new line characters for formatting. Therefore, we call the method
			// Util.getValidationResultsHtml() to convert these characters to <br>'s and &nbsp;'s.
			model.addAttribute("vrHtml", Util.getValidationResultsHtml(vr));
			model.addAttribute("retryUrl", "/authentication");
			return "validation-failed";
		}

		PKCertificate userCert = auth.getPKCertificate();

		// At this point, you have assurance that the certificate is valid according to the
		// SecurityContext passed on the first step (see method get()) and that the user is indeed the certificate's
		// subject. Now, you'd typically query your database for a user that matches one of the
		// certificate's fields, such as userCert.getEmailAddress() or userCert.getPkiBrazil().getCpf() (the actual field
		// to be used as key depends on your application's business logic) and set the user as authenticated with whatever
		// web security framework your application uses. For demonstration purposes, we'll just render the user's
		// certificate information.

		model.addAttribute("userCert", userCert);
		return "authentication-success";
	}
}

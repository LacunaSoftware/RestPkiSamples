package sample.controller;

import com.lacunasoftware.restpki.*;
import org.springframework.util.StringUtils;
import org.springframework.web.bind.annotation.*;

import sample.models.AuthenticationPostResponse;
import sample.models.AuthenticationPostRequest;
import sample.util.*;

/**
 * This controller contains the server-side logic for the authentication example. The client-side is implemented at:
 * - View: src/main/resources/template/authentication.html
 * - JS: src/main/resources/static/js/app/authentication.js
 * <p>
 * This controller uses the com.lacunasoftware.restpki.Authentication class to implement the authentication. For more
 * information, see:
 * https://restpki.lacunasoftware.com/Content/docs/java-client/index.html?com/lacunasoftware/restpki/Authentication.html
 */
@RestController
public class AuthenticationController {

	/**
	 * GET api/authentication
	 * <p>
	 * This action is called once the user clicks the "Sign In" button.
	 */
	@RequestMapping(value = "/api/authentication", method = {RequestMethod.GET})
	public String get() throws RestException {

		// Instantiate the Authentication class
		Authentication auth = new Authentication(Util.getRestPkiClient());

		// Call the Authentication.start() method, which initiates the authentication. This yields the nonce,
		// a 16-byte-array encoded in standard Base64 (24-character case-sensitive string, NOT url-safe) which must be
		// signed using the user certificate's private key (for this we'll use the signData method of the Web PKI
		// component)
		String nonce = auth.start();

		// Return the nonce to the page
		return nonce;
	}

	/**
	 * POST api/authentication?token=xxx
	 * <p>
	 * This action is called after signing the nonce on the client-side with the user's certificate. We'll once
	 * again use the Authentication class to do the actual work.
	 */
	@RequestMapping(value = "/api/authentication", method = {RequestMethod.POST})
	public AuthenticationPostResponse post(@RequestBody AuthenticationPostRequest request) throws RestException {

		// Instantiate the Authentication class
		Authentication auth = new Authentication(Util.getRestPkiClient());

		// Call the complete() method, which finalizes the authentication process. It receives as input:
		// - The nonce which was signed using the user's certificate
		// - The user's certificate encoding
		// - The nonce signature
		// - A security context, which controls the validation
		// The call yields a ValidationResults which denotes whether the authentication was successful or not.

		// Note on encodings: the nonce, certificate and signature are byte arrays encoded as Base64 strings, which is
		// exactly the format that the Web PKI component outputs (see the authentication.js file for more information).

		ValidationResults vr = auth.complete(request.getNonce(), request.getCertificate(), request.getSignature(), SecurityContext.pkiBrazil);
		// >>> NOTE: By changing the SecurityContext above you can accept only certificates from a certain PKI,
		// for instance ICP-Brasil (SecurityContext.pkiBrazil). You can also define a custom security context on the
		// REST PKI website accepting whatever root certification authorities you wish.

		AuthenticationPostResponse response = new AuthenticationPostResponse();

		// Check the authentication result
		if (!vr.isValid()) {
			// If the authentication failed, inform the page
			response.setSuccess(false);
			response.setMessage("Authentication failed");
			response.setValidationResults(vr.toString());
			return response;
		}

		// At this point, you have assurance that the certificate is valid according to the
		// SecurityContext passed on the first step (see method get()) and that the user is indeed the certificate's
		// subject. Now, you'd typically query your database for a user that matches one of the
		// certificate's fields, such as cert.getEmailAddress() or cert.getPkiBrazil().getCpf() (the actual field
		// to be used as key depends on your application's business logic) and set the user
		// as authenticated with whatever web security framework your application uses.
		// For demonstration purposes, we'll just return a success and put on the message something
		// to show that we have access to the certificate's fields.

		PKCertificate userCert = auth.getPKCertificate();
		StringBuilder message = new StringBuilder();
		message.append("Welcome, " + userCert.getSubjectName().getCommonName() + "!");
		if (!StringUtils.isEmpty(userCert.getEmailAddress())) {
			message.append(" Your email address is " + userCert.getEmailAddress());
		}
		if (!StringUtils.isEmpty(userCert.getPkiBrazil().getCpf())) {
			message.append(" and your CPF is " + userCert.getPkiBrazil().getCpf());
		}

		// Return success to the page
		response.setSuccess(true);
		response.setMessage(message.toString());
		return response;
	}

}

package sample.controller;

import com.lacunasoftware.restpki.*;
import org.springframework.util.StringUtils;
import org.springframework.web.bind.annotation.*;

import sample.models.AuthenticationPostResponse;
import sample.util.*;

/**
 * This controller contains the server-side logic for the authentication example. The client-side is implemented at:
 * - HTML: src/main/resources/template/Authentication.cshtml
 * - JS: src/main/resources/static/js/app/authentication.js
 *
 * This controller uses the Authentication class to implement the authentication. For more information, see:
 * http://pki.lacunasoftware.com/Help/html/c7e43b5d-f745-43a7-92dc-74e777c1caa0.htm
 */
@RestController
public class AuthenticationController {

    /**
     * GET Api/Authentication
     *
     *	This action is called once the user clicks the "Sign In" button. It uses the Authentication
     *	class to generate and store a cryptographic nonce, which will then be sent to the page for signature using
     *	the user's certificate.
     */
	@RequestMapping (value = "/api/authentication", method = {RequestMethod.GET})
    public String get() throws RestException {
        // Instantiate the RestPkiClient
		RestPkiClient client = new RestPkiClient(Util.getRestPkiEndpoint(), Util.getAuthToken());

        // Call the Authentication start() method. which is the first of the two server-side steps. This yields the token,
        // a 22-character case-sensitive string, which we'll send to the page in order to pass on the signWithRestPki
        // method of the Web PKI component.
		String token = new Authentication(client).startWithWebPki(Util.getSecurityContext());

		return token;
    }

    /**
     * POST Api/Authentication
     *
     * This action is called after signing the nonce on the client-side with the user's certificate. We'll once
     * again use the Authentication class to do the actual work.
     */
	@RequestMapping (value = "/api/authentication", method = {RequestMethod.POST})
    public AuthenticationPostResponse post(@RequestParam(value="token", required=true) String token) throws RestException {
        // Instantiate the RestPkiClient
        RestPkiClient client = new RestPkiClient(Util.getRestPkiEndpoint(), Util.getAuthToken());
        // Instantiate the Authentication class passing our rest pki client
		Authentication auth = new Authentication(client);

        // Call the complete() method, which is the last of the two server-side steps. It receives:
        // nonce           - The nonce which was signed using the user's certificate
        // certificate     - The user's certificate encoding
        // signature       - The nonce signature
        // securityContext - A SecurityContext to be used to determine trust in the certificate chain
        // The call yields:
        // - A ValidationResults which denotes whether the authentication was successful or not
		ValidationResults vr = auth.completeWithWebPki(token);

        // Note: By changing the SecurityContext above you can accept only certificates from a certain PKI,
        // for instance, ICP-Brasil (SecurityContext.pkiBrazil).

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
        // SecurityContext you selected above and that the user is indeed the certificate's
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

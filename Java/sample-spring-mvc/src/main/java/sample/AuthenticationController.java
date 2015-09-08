package sample;

import java.io.IOException;

import com.lacunasoftware.restpki.*;
import org.springframework.util.StringUtils;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;
import org.springframework.web.bind.annotation.RestController;

import sample.models.AuthenticationGetResponse;
import sample.models.AuthenticationPostRequest;
import sample.models.AuthenticationPostResponse;

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
	@RequestMapping (value = "/Api/Authentication", method = {RequestMethod.GET})
    public AuthenticationGetResponse get() throws RestException {
        // Instantiate the RestPkiClient
		RestPkiClient client = new RestPkiClient(Util.getRestPkiEndpoint(), Util.getAuthToken());

        // Call the Authentication start() method. which is the first of the two server-side steps. This yields the nonce,
        // a 16-byte-array encoded in base64, which we'll send to the page. If you are using the Lacuna Web PKI component to
        // perform the client-side signature, this value is the exact argument that you must pass to signData()
		String nonce = new Authentication(client).start();

        // Instantiate an authentication response with the returned nonce and send it back to the page
		AuthenticationGetResponse response = new AuthenticationGetResponse();
		response.nonce = nonce;
		return response;
    }

    /**
     * POST Api/Authentication
     *
     * This action is called after signing the nonce on the client-side with the user's certificate. We'll once
     * again use the Authentication class to do the actual work.
     */
	@RequestMapping (value = "/Api/Authentication", method = {RequestMethod.POST})
    public AuthenticationPostResponse post(@RequestBody AuthenticationPostRequest request) throws RestException {
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
		ValidationResults vr = auth.complete(request.nonce, request.certificate, request.signature, Util.getSecurityContext());

        // Note: By changing the SecurityContext above you can accept only certificates from a certain PKI,
        // for instance, ICP-Brasil (SecurityContext.pkiBrazil).

        AuthenticationPostResponse response = new AuthenticationPostResponse();

        // Check the authentication result
		if (!vr.isValid()) {
            // If the authentication failed, inform the page
			response.success = false;
			response.message = "Authentication failed";
			response.validationResults = vr.toString();
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
        response.success = true;
        response.message = message.toString();
		return response;
    }

}

package sample.controller;

import com.lacunasoftware.restpki.*;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;
import org.springframework.web.bind.annotation.RequestParam;
import sample.Application;
import sample.util.Util;

import javax.servlet.http.HttpServletResponse;
import java.io.IOException;
import java.nio.file.Files;
import java.util.UUID;

@Controller
public class CadesSignatureController {

	/*
	 * This action renders the page for the first step of the signature process, on which the user will choose the
	 * certificate to be used to sign the file and we'll programmatically retrieve the certificate's encoding using
	 * the Web PKI component and send back to the server.
	 *
	 * All CAdES signature examples converge to this action, but with different URL arguments:
	 *
	 * 1. Signature with a server file               : no arguments filled
	 * 2. Signature with a file uploaded by the user : "userfile" filled
	 * 3. Co-signature of a previously signed CMS    : "cmsfile" filled
	 */
	@RequestMapping(value = "/cades-signature", method = {RequestMethod.GET})
	public String get(
		@RequestParam(value = "userfile", required = false) String userfile,
		@RequestParam(value = "cmsfile", required = false) String cmsfile,
		Model model
	) throws IOException, RestException {
		model.addAttribute("userfile", userfile);
		model.addAttribute("cmsfile", cmsfile);
		// Render the page for the first step of the signature process, on which the user chooses the certificate and
		// its encoding is read (templates/cades-signature-step1.html)
		return "cades-signature-step1";
	}

	/*
	 * This action receives the encoding of the certificate chosen by the user, uses it to initiate a CAdES signature
	 * using REST PKI and renders the page for the final step of the signature process.
    */
	@RequestMapping(value = "/cades-signature", method = {RequestMethod.POST})
	public String post(
		@RequestParam(value = "selectedCertThumb", required = true) String selectedCertThumb,
		@RequestParam(value = "certificate", required = true) String certificate,
		@RequestParam(value = "userfile", required = false) String userfile,
		@RequestParam(value = "cmsfile", required = false) String cmsfile,
		Model model,
		HttpServletResponse response
	) throws IOException, RestException {

		// Instantiate the CadesSignatureStarter class, responsible for receiving the signature elements and start the
		// signature process. For more information, see:
		// https://pki.rest/Content/docs/java-client/index.html?com/lacunasoftware/restpki/CadesSignatureStarter.html
		CadesSignatureStarter signatureStarter = new CadesSignatureStarter(Util.getRestPkiClient());

		if (userfile != null && !userfile.isEmpty()) {

			// If the URL argument "userfile" is filled, it means the user was redirected to the get() method (above) by
			// UploadController (signature with file uploaded by user). We'll set the path of the file to be signed, which
			// was saved in the temporary folder by UploadController (such a file would normally come from your
			// application's database)
			signatureStarter.setFileToSign(Application.getTempFolderPath().resolve(userfile));

		} else if (cmsfile != null && !cmsfile.isEmpty()) {

			/*
			 * If the URL argument "cmsfile" is filled, the user has asked to co-sign a previously signed CMS. We'll set
			 * the path to the CMS to be co-signed, which was previously saved in our temporary folder. Note two important
			 * things:
			 *
			 * 1. The CMS to be co-signed must be set using the method "setCmsToCoSign", not the method "setContentToSign"
			 *    nor "setFileToSign"
			 *
			 * 2. Since we're creating CMSs with encapsulated content (see call to setEncapsulateContent() below), we
			 *    don't need to set the content to be signed, REST PKI will get the content from the CMS being co-signed.
			 */
			signatureStarter.setCmsToCoSign(Application.getTempFolderPath().resolve(cmsfile));

		} else {

			// If both userfile and cmsfile are null, this is the "signature with server file" case.
			signatureStarter.setContentToSign(Util.getSampleDocContent());

		}

		// Set the certificate's encoding in base64 encoding (which is what the Web PKI component yields)
		signatureStarter.setSignerCertificate(certificate);

		// Set the signature policy
		signatureStarter.setSignaturePolicy(SignaturePolicy.PkiBrazilAdrBasica);

		// Optionally, set a SecurityContext to be used to determine trust in the certificate chain
		//signatureStarter.setSecurityContext(SecurityContext.pkiBrazil);
		// Note: Depending on the signature policy chosen above, setting the security context may be mandatory (this is
		// not the case for ICP-Brasil policies, which will automatically use the ICP-Brasil security context
		// ("pkiBrazil") if none is passed)

		// Optionally, set whether the content should be encapsulated in the resulting CMS. If this parameter is omitted
		// or set to null, the following rules apply:
		// - If no CmsToSign is given, the resulting CMS will include the content
		// - If a CmsToCoSign is given, the resulting CMS will include the content if and only if the CmsToCoSign also
		//   includes the content
		signatureStarter.setEncapsulateContent(true);

		// Call the start() method, which initiates the signature on REST PKI. This yields the parameters for the
		// client-side signature, which we'll use to render the page for the final step, where the actual signature will
		// be performed.
		ClientSideSignatureInstructions signatureInstructions;
		try {
			signatureInstructions = signatureStarter.start();
		} catch (ValidationException e) {
			// The call above may throw a ValidationException if the certificate fails the initial validations (for
			// instance, if it is expired). If so, we'll render a page showing what went wrong.
			model.addAttribute("title", "Validation of the certificate failed");
			// The toString() method of the ValidationResults object can be used to obtain the checks performed, but the
			// string contains tabs and new line characters for formatting. Therefore, we call the method
			// Util.getValidationResultsHtml() to convert these characters to <br>'s and &nbsp;'s.
			model.addAttribute("vrHtml", Util.getValidationResultsHtml(e.getValidationResults()));
			String retryUrl = "/cades-signature";
			if (userfile != null && !userfile.isEmpty()) {
				retryUrl += "?userfile=" + userfile;
			} else if (cmsfile != null && !cmsfile.isEmpty()) {
				retryUrl += "?cmsfile=" + cmsfile;
			}
			model.addAttribute("retryUrl", retryUrl);
			return "validation-failed";
		}

		// Among the data returned by the start() method is the token, a string which identifies this signature process.
		// This token can only be used for a single signature attempt. In order to retry the signature it is
		// necessary to get a new token. This can be a problem if the user uses the back button of the browser, since the
		// browser might show a cached page that we rendered previously, with a now stale token. To prevent this from
		// happening, we call the method Util.setNoCacheHeaders(), which sets HTTP headers to prevent caching of the page.
		Util.setNoCacheHeaders(response);

		// Render the page for the final step of the signature process, on which the actual signature will be performed
		// (templates/cades-signature-step2.html)
		model.addAttribute("selectedCertThumb", selectedCertThumb);
		model.addAttribute("token", signatureInstructions.getToken());
		model.addAttribute("toSignHash", signatureInstructions.getToSignHash());
		model.addAttribute("digestAlg", signatureInstructions.getDigestAlgorithmOid());
		return "cades-signature-step2";
	}

	/*
	 * This action receives the form submission from the page for the final step of the signature process. We'll call
	 * REST PKI to complete the signature.
	 */
	@RequestMapping(value = "/cades-signature-complete", method = {RequestMethod.POST})
	public String complete(
		@RequestParam(value = "token", required = true) String token,
		@RequestParam(value = "signature", required = true) String signature,
		Model model
	) throws IOException, RestException {

		// Instantiate the PadesSignatureFinisher class, responsible for completing the signature process. For more
		// information, see:
		// https://pki.rest/Content/docs/java-client/index.html?com/lacunasoftware/restpki/CadesSignatureFinisher.html
		CadesSignatureFinisher signatureFinisher = new CadesSignatureFinisher(Util.getRestPkiClient());

		// Set the token for this signature (rendered in a hidden input field, see file templates/cades-signature-step2.html)
		signatureFinisher.setToken(token);

		// Set the result of the signature operation
		signatureFinisher.setSignature(signature);

		// Call the finish() method, which finalizes the signature process and returns the CMS
		byte[] cms;
		try {
			cms = signatureFinisher.finish();
		} catch (ValidationException e) {
			// The call above may throw a ValidationException if any validation errors occur (for instance, if the
			// certificate is revoked). If so, we'll render a page showing what went wrong.
			model.addAttribute("title", "Validation of the signature failed");
			// The toString() method of the ValidationResults object can be used to obtain the checks performed, but the
			// string contains tabs and new line characters for formatting. Therefore, we call the method
			// Util.getValidationResultsHtml() to convert these characters to <br>'s and &nbsp;'s.
			model.addAttribute("vrHtml", Util.getValidationResultsHtml(e.getValidationResults()));
			return "validation-failed";
		}

		// Get information about the certificate used by the user to sign the file. This method must only be called after
		// calling the finish() method.
		PKCertificate signerCert = signatureFinisher.getCertificateInfo();

		// At this point, you'd typically store the CMS on your database. For demonstration purposes, we'll
		// store the CMS on a temporary folder and return to the page an identifier that can be used to download it.

		String filename = UUID.randomUUID() + ".p7s";
		Files.write(Application.getTempFolderPath().resolve(filename), cms);
		model.addAttribute("signerCert", signerCert);
		model.addAttribute("filename", filename);
		return "cades-signature-info";
	}
}

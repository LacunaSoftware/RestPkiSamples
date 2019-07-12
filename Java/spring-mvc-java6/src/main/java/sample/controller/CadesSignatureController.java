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
import java.io.File;
import java.io.IOException;
import java.util.UUID;

@Controller
public class CadesSignatureController {

	/**
	 * This action initiates a CAdES signature using REST PKI and renders the signature page.
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
		Model model,
		HttpServletResponse response
	) throws IOException, RestException {

		// Get an instance of the CadesSignatureStarter2 class, responsible for receiving the
		// signature elements and start the signature process.
		CadesSignatureStarter2 signatureStarter = new CadesSignatureStarter2(Util.getRestPkiClient());

		// Set the signature policy.
		signatureStarter.setSignaturePolicy(SignaturePolicy.PkiBrazilAdrBasica);

		// Set the security context to be used to determine trust in the certificate chain. We have
		// encapsulated the security context choice on Util.java.
		signatureStarter.setSecurityContext(Util.getSecurityContextId());

		// Optionally, set whether the content should be encapsulated in the resulting CMS. If this
		// parameter is omitted or set to null, the following rules apply:
		// - If no CmsToCoSign is given, the resulting CMS will include the content;
		// - If a CmsToCoSign is given, the resulting CMS will include the content if and only if the
		//   CmsToCoSign also includes the content.
		signatureStarter.setEncapsulateContent(true);

		// Below we'll either set the file to be signed or the CMS to be co-signed. Prefer passing a
		// path or a stream instead of the file's contents as a byte array to prevent memory
		// allocation issues with large files.
		if (userfile != null && !userfile.isEmpty()) {

			// If the URL argument "userfile" is filled, it means the user was redirected here by
			// UploadController (signature with file uploaded by user). We'll set the path of the file
			// to be signed, which was saved in the temporary folder by UploadController (such a file
			// would normally come from your application's database).
			signatureStarter.setFileToSign(new File(Application.getTempFolderPath(), userfile));

		} else if (cmsfile != null && !cmsfile.isEmpty()) {

			/*
				If the URL argument "cmsfile" is filled, the user has asked to co-sign a previously
				signed CMS. We'll set the path to the CMS to be co-signed, which was previously saved
				in our temporary folder by the complete() method on this controller. Note two important
				things:

				   1. The CMS to be co-signed must be set using the method "setCmsToCoSign", not the
				      method "setContentToSign" nor "setFileToSign".

				   2. Since we're creating CMSs with encapsulated content (see call to
				      setEncapsulateContent() below), we don't need to set the content to be signed,
				      REST PKI will get the content from the CMS being co-signed.
			*/
			signatureStarter.setCmsToCoSign(new File(Application.getTempFolderPath(), cmsfile));

		} else {

			// If both userfile and cmsfile are null, this is the "signature with server file" case.
			// We'll set the file to be signed as a byte array.
			signatureStarter.setFileToSign(Util.getSampleDocFile());

		}

		// Call the startWithWebPki() method, which initiates the signature. This yields a
		// SignatureStartWithWebPkiResult object containing the signer certificate and the token, a
		// 43-character case-sensitive URL-safe string, which identifies this signature process.
		// We'll use this value to call the signWithRestPki() method on the Web PKI component
		// (see file static/js/signature-form.js) and also to complete the signature after the form
		// is submitted (see method complete() below). This should not be mistaken with the API
		// access token.
		SignatureStartWithWebPkiResult result = signatureStarter.startWithWebPki();

		// The token acquired above can only be used for a single signature attempt. In order to
		// retry the signature it is necessary to get a new token. This can be a problem if the user
		// uses the back button of the browser, since the browser might show a cached page that we
		// rendered previously, with a now stale token. To prevent this from happening, we call the
		// method Util.setNoCacheHeaders(), which sets HTTP headers to prevent caching of the page.
		Util.setNoCacheHeaders(response);

		// Render the signature page (templates/cades-signature.html).
		model.addAttribute("token", result.getToken());
		model.addAttribute("userfile", userfile);
		model.addAttribute("cmsfile", cmsfile);
		return "cades-signature";
	}

	/**
	 * This action receives the form submission from the signature page. We'll call REST PKI to
	 * complete the signature.
	 */
	@RequestMapping(value = "/cades-signature", method = {RequestMethod.POST})
	public String post(
		@RequestParam(value = "token", required = true) String token,
		Model model
	) throws IOException, RestException {

		// Get an instance of the CadesSignatureFinisher2 class, responsible for completing the
		// signature process.
		CadesSignatureFinisher2 signatureFinisher = new CadesSignatureFinisher2(Util.getRestPkiClient());

		// Set the token for this signature (rendered in a hidden input field, see file
		// templates/cades-signature.html).
		signatureFinisher.setToken(token);

		// Call the finish() method, which finalizes the signature process and returns a
		// SignatureResult object.
		SignatureResult signatureResult = signatureFinisher.finish();

		// The "Certificate" property of the SignatureResult object contains information about the
		// certificate used by the user to sign the file.
		PKCertificate signerCert = signatureResult.getCertificate();

		// At this point, you'd typically store the CMS on your database. For demonstration purposes,
		// we'll store the CMS on a temporary folder and return to the page an identifier that can be
		// used to download it.

		// The SignatureResult object has various methods for writing the signature file to a stream
		// (writeToFile()), local file (writeToFile()), get its contents (getContent()). Avoid these
		// methods to prevent memory allocation issues with large files.
		String filename = UUID.randomUUID() + ".p7s";
		signatureResult.writeToFile(new File(Application.getTempFolderPath(), filename));

		// Render the signature page (templates/cades-signature-info.html).
		model.addAttribute("signerCert", signerCert);
		model.addAttribute("filename", filename);
		return "cades-signature-info";
	}
}

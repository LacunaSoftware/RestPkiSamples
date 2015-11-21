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

		// Instantiate the CadesSignatureStarter class, responsible for receiving the signature elements and start the
		// signature process. For more information, see:
		// https://pki.rest/Content/docs/java-client/index.html?com/lacunasoftware/restpki/CadesSignatureStarter.html
		CadesSignatureStarter signatureStarter = new CadesSignatureStarter(Util.getRestPkiClient());

		if (userfile != null && !userfile.isEmpty()) {

			// If the URL argument "userfile" is filled, it means the user was redirected here by UploadController
			// (signature with file uploaded by user). We'll set the path of the file to be signed, which was saved in the
			// temporary folder by UploadController (such a file would normally come from your application's database)
			signatureStarter.setFileToSign(Application.getTempFolderPath().resolve(userfile));

		} else if (cmsfile != null && !cmsfile.isEmpty()) {

			/*
			 * If the URL argument "cmsfile" is filled, the user has asked to co-sign a previously signed CMS. We'll set
			 * the path to the CMS to be co-signed, which was previously saved in our temporary folder by the post()
			 * method on this controller. Note two important things:
			 *
			 * 1. The CMS to be co-signed must be set using the method "setCmsToCoSign", not the method "setContentToSign"
			 *    nor "setFileToSign"
			 *
			 * 2. Since we're creating CMSs with encapsulated content (see call to setEncapsulateContent() below), we
			 *    don't need to set the content to be signed, REST PKI will get the content from the CMS being co-signed.
			 */
			signatureStarter.setCmsToCoSign(Application.getTempFolderPath().resolve(cmsfile));

		} else {

			// If both userfile and cmsfile are null, this is the "signature with server file" case. We'll set the file to
			// be signed as a byte array
			signatureStarter.setContentToSign(Util.getSampleDocContent());

		}

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

		// Call the startWithWebPki() method, which initiates the signature. This yields the token, a 43-character
		// case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
		// signWithRestPki() method on the Web PKI component (see file signature-form.js) and also to complete the
		// signature after the form is submitted (see method post() below). This should not be mistaken with the API
		// access token.
		String token = signatureStarter.startWithWebPki();

		// The token acquired above can only be used for a single signature attempt. In order to retry the signature it is
		// necessary to get a new token. This can be a problem if the user uses the back button of the browser, since the
		// browser might show a cached page that we rendered previously, with a now stale token. To prevent this from
		// happening, we call the method Util.setNoCacheHeaders(), which sets HTTP headers to prevent caching of the page.
		Util.setNoCacheHeaders(response);

		// Render the signature page (templates/cades-signature.html)
		model.addAttribute("token", token);
		model.addAttribute("userfile", userfile);
		model.addAttribute("cmsfile", cmsfile);
		return "cades-signature";
	}

	/*
	 * This action receives the form submission from the signature page. We'll call REST PKI to complete the signature.
	 */
	@RequestMapping(value = "/cades-signature", method = {RequestMethod.POST})
	public String post(
		@RequestParam(value = "token", required = true) String token,
		Model model
	) throws IOException, RestException {

		// Instantiate the PadesSignatureFinisher class, responsible for completing the signature process. For more
		// information, see:
		// https://pki.rest/Content/docs/java-client/index.html?com/lacunasoftware/restpki/CadesSignatureFinisher.html
		CadesSignatureFinisher signatureFinisher = new CadesSignatureFinisher(Util.getRestPkiClient());

		// Set the token for this signature (rendered in a hidden input field, see file templates/cades-signature.html)
		signatureFinisher.setToken(token);

		// Call the finish() method, which finalizes the signature process and returns the CMS
		byte[] cms = signatureFinisher.finish();

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

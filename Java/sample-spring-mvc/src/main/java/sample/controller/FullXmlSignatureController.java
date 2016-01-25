package sample.controller;

import com.lacunasoftware.restpki.*;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.*;
import sample.Application;
import sample.util.*;

import javax.servlet.http.HttpServletResponse;
import java.io.IOException;
import java.nio.file.Files;
import java.util.UUID;

@Controller
public class FullXmlSignatureController {

	/*
	 * This action initiates a full XML signature using REST PKI and renders the signature page.
	 */
	@RequestMapping(value = "/xml-full-signature", method = {RequestMethod.GET})
	public String get(
		Model model,
		HttpServletResponse response
	) throws IOException, RestException {

		// Instantiate the FullXmlSignatureStarter class, responsible for receiving the signature elements and start the
		// signature process. For more information, see:
		// https://pki.rest/Content/docs/java-client/index.html?com/lacunasoftware/restpki/FullXmlSignatureStarter.html
		FullXmlSignatureStarter signatureStarter = new FullXmlSignatureStarter(Util.getRestPkiClient());

        // Set the XML to be signed, a sample XML Document
        signatureStarter.setXml(Util.getSampleXml());

        // Set the location on which to insert the signature node. If the location is not specified, the signature will appended
        // to the root element (which is most usual with enveloped signatures).
        XmlNamespaceManager nsm = new XmlNamespaceManager();
        nsm.addNamespace("ls", "http://www.lacunasoftware.com/sample");
        signatureStarter.setSignatureElementLocation("//ls:signaturePlaceholder", nsm, XmlInsertionOptions.AppendChild);

		// Set the signature policy
		signatureStarter.setSignaturePolicy(SignaturePolicy.XadesBasic);

		// Set a SecurityContext to be used to determine trust in the certificate chain
		signatureStarter.setSecurityContext(SecurityContext.pkiBrazil);
		// Note: By changing the SecurityContext above you can accept only certificates from a certain PKI,
		// for instance, ICP-Brasil (SecurityContext.pkiBrazil).

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

		// Render the signature page (templates/Xml-full-signature.html)
		model.addAttribute("token", token);
		return "xml-full-signature";
	}

	/*
	 * This action receives the form submission from the signature page. We'll call REST PKI to complete the signature.
	 */
	@RequestMapping(value = "/xml-full-signature", method = {RequestMethod.POST})
	public String post(
		@RequestParam(value = "token", required = true) String token,
		Model model
	) throws IOException, RestException {

		// Instantiate the XmlSignatureFinisher class, responsible for completing the signature process. For more
		// information, see:
		// https://pki.rest/Content/docs/java-client/index.html?com/lacunasoftware/restpki/XmlSignatureFinisher.html
		XmlSignatureFinisher signatureFinisher = new XmlSignatureFinisher(Util.getRestPkiClient());

		// Set the token for this signature (rendered in a hidden input field, see file templates/pades-signature.html)
		signatureFinisher.setToken(token);

		// Call the finish() method, which finalizes the signature process and returns the signed XML's bytes
		byte[] signedXml = signatureFinisher.finish();

		// Get information about the certificate used by the user to sign the file. This method must only be called after
		// calling the finish() method.
		PKCertificate signerCert = signatureFinisher.getCertificateInfo();

		// At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
		// store the XML on a temporary folder and return to the page an identifier that can be used to download it.

		String filename = UUID.randomUUID() + ".xml";
		Files.write(Application.getTempFolderPath().resolve(filename), signedXml);
		model.addAttribute("signerCert", signerCert);
		model.addAttribute("filename", filename);
		return "xml-signature-info";
	}
}

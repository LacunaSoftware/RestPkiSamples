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
@SuppressWarnings("Duplicates")
public class XmlFullSignatureController {

	/**
	 * This action initiates a full XML signature using REST PKI and renders the signature page.
	 * The full XML signature is recommended in cases which there is a need to sign the whole XML
	 * file.
	 */
	@RequestMapping(value = "/xml-full-signature", method = {RequestMethod.GET})
	public String get(
		Model model,
		HttpServletResponse response
	) throws IOException, RestException {

		// Instantiate the FullXmlSignatureStarter class, responsible for receiving the signature
		// elements and start the signature process.
		FullXmlSignatureStarter signatureStarter = new FullXmlSignatureStarter(Util.getRestPkiClient());

		// Set path of the XML to be signed, a sample XML Document.
		signatureStarter.setXml(Util.getSampleXmlPath());

		// Set the signature policy.
		signatureStarter.setSignaturePolicy(SignaturePolicy.XadesBasic);

		// Set the security context to be used to determine trust in the certificate chain. We have
		// encapsulated the security context choice on Util.cs.
		signatureStarter.setSecurityContext(Util.getSecurityContextId());

		// Set the location on which to insert the signature node. If the location is not specified,
		// the signature will appended to the root element (which is most usual with enveloped
		// signatures).
		XmlNamespaceManager nsm = new XmlNamespaceManager();
		nsm.addNamespace("ls", "http://www.lacunasoftware.com/sample");
		signatureStarter.setSignatureElementLocation("//ls:signaturePlaceholder", nsm, XmlInsertionOptions.AppendChild);

		// Call the startWithWebPki() method, which initiates the signature. This yields the token,
		// a 43-character case-sensitive URL-safe string, which identifies this signature process.
		// We'll use this value to call the signWithRestPki() method on the Web PKI component
		// (see file signature-form.js) and also to complete the signature after the form is
		// submitted (see method complete() below). This should not be mistaken with the API access
		// token.
		String token = signatureStarter.startWithWebPki();

		// The token acquired above can only be used for a single signature attempt. In order to
		// retry the signature it is necessary to get a new token. This can be a problem if the user
		// uses the back button of the browser, since the browser might show a cached page that we
		// rendered previously, with a now stale token. To prevent this from happening, we call the
		// method Util.setNoCacheHeaders(), which sets HTTP headers to prevent caching of the page.
		Util.setNoCacheHeaders(response);

		// Render the signature page (templates/xml-full-signature.html).
		model.addAttribute("token", token);
		return "xml-full-signature";
	}

	/**
	 * This action receives the form submission from the signature page. We'll call REST PKI to
	 * complete the signature.
	 */
	@RequestMapping(value = "/xml-full-signature", method = {RequestMethod.POST})
	public String post(
		@RequestParam(value = "token") String token,
		Model model
	) throws IOException, RestException {

		// Instantiate the XmlSignatureFinisher class, responsible for completing the signature
		// process.
		XmlSignatureFinisher signatureFinisher = new XmlSignatureFinisher(Util.getRestPkiClient());

		// Set the token for this signature (rendered in a hidden input field, see
		// file templates/xml-full-signature.html).
		signatureFinisher.setToken(token);

		// Call the finish() method, which finalizes the signature process and returns the signed
		// XML's bytes.
		byte[] signedXml = signatureFinisher.finish();

		// Get information about the certificate used by the user to sign the file. This method must
		// only be called after calling the finish() method.
		PKCertificate signerCert = signatureFinisher.getCertificateInfo();

		// At this point, you'd typically store the signed PDF on your database. For demonstration
		// purposes, we'll store the XML on a temporary folder and return to the page an identifier
		// that can be used to download it.

		String filename = UUID.randomUUID() + ".xml";
		Files.write(Application.getTempFolderPath().resolve(filename), signedXml);

		// Render the signature page (templates/xml-signature-info.html).
		model.addAttribute("signerCert", signerCert);
		model.addAttribute("filename", filename);
		return "xml-signature-info";
	}
}

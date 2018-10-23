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

/**
 * This controller performs two signatures on the same XML document, one on each element, according
 * to the standard Certificación de Origen Digital (COD), from Asociación Latinoamericana de
 * Integración (ALADI). For more information, please see:
 *
 * - Spanish: http://www.aladi.org/nsfweb/Documentos/2327Rev2.pdf
 * - Portuguese: http://www.mdic.gov.br/images/REPOSITORIO/secex/deint/coreo/2014_09_19_-_Brasaladi_761_-_Documento_ALADI_SEC__di_2327__Rev_2_al_port_.pdf
 */
@Controller
public class CodXmlSignatureController {

	/**
	 * This action simple renders the initial page.
	 */
	@RequestMapping(value = "/cod-xml-signature", method = {RequestMethod.GET})
	public String get() {

		// Renders the page (templates/cod-xml-signature.html).
		return "cod-xml-signature";
	}

	@RequestMapping(value = "/cod-xml-signature-sign-cod", method = {RequestMethod.GET})
	public String getStartCod(
		Model model,
		HttpServletResponse response
	) throws IOException, RestException {

		// Instantiate the XmlElementSignatureStarter class, responsible for receiving the signature
		// elements and start the signature process.
		XmlElementSignatureStarter signatureStarter = new XmlElementSignatureStarter(Util.getRestPkiClient());

		// Set the path of the XML to be signed, a sample Brazilian fiscal invoice pre-generated.
		signatureStarter.setXml(Util.getSampleCodEnvelopePath());

		// Set the ID of the element to be signed.
		signatureStarter.setElementToSIgnId("COD");

		// Set the signature policy.
		signatureStarter.setSignaturePolicy(SignaturePolicy.CodSha1);

		// Set the security context to be used to determine trust in the certificate chain. We have
		// encapsulated the security context choice on Util.java.
		signatureStarter.setSecurityContext(Util.getSecurityContextId());

		// Call the startWithWebPki() method, which initiates the signature. This yields the token,
		// a 43-character case-sensitive URL-safe string, which identifies this signature process.
		// We'll use this value to call the signWithRestPki() method on the Web PKI component
		// (see file signature-form.js) and also to complete the signature after the form is
		// submitted (see method post() below). This should not be mistaken with the API access
		// token.
		String token = signatureStarter.startWithWebPki();

		// The token acquired above can only be used for a single signature attempt. In order to
		// retry the signature it is necessary to get a new token. This can be a problem if the user
		// uses the back button of the browser, since the browser might show a cached page that we
		// rendered previously, with a now stale token. To prevent this from happening, we call the
		// method Util.setNoCacheHeaders(), which sets HTTP headers to prevent caching of the page.
		Util.setNoCacheHeaders(response);

		// Render the signature page (templates/cod-xml-signature-sign-cod.html).
		model.addAttribute("token", token);
		return "cod-xml-signature-sign-cod";
	}

	@RequestMapping(value = "/cod-xml-signature-sign-cod", method = {RequestMethod.POST})
	public String postCompleteCod(
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

		// Render the signature page (templates/cod-xml-signature-sign-cod-result.html).
		model.addAttribute("signerCert", signerCert);
		model.addAttribute("filename", filename);
		return "cod-xml-signature-sign-cod-result";
	}

	@RequestMapping(value = "/cod-xml-signature-sign-codeh", method = {RequestMethod.GET})
	public String getStartCodeh(
		@RequestParam(value = "userfile") String userfile,
		Model model,
		HttpServletResponse response
	) throws IOException, RestException {

		// Instantiate the XmlElementSignatureStarter class, responsible for receiving the signature
		// elements and start the signature process.
		XmlElementSignatureStarter signatureStarter = new XmlElementSignatureStarter(Util.getRestPkiClient());

		// Set the path of the XML to be signed, a sample Brazilian fiscal invoice pre-generated.
		signatureStarter.setXml(Application.getTempFolderPath().resolve(userfile));

		// Set the ID of the element to be signed.
		signatureStarter.setElementToSIgnId("CODEH");

		// Set the signature policy.
		signatureStarter.setSignaturePolicy(SignaturePolicy.CodSha1);

		// Set the security context to be used to determine trust in the certificate chain. We have
		// encapsulated the security context choice on Util.java.
		signatureStarter.setSecurityContext(Util.getSecurityContextId());

		// Call the startWithWebPki() method, which initiates the signature. This yields the token,
		// a 43-character case-sensitive URL-safe string, which identifies this signature process.
		// We'll use this value to call the signWithRestPki() method on the Web PKI component
		// (see file signature-form.js) and also to complete the signature after the form is
		// submitted (see method post() below). This should not be mistaken with the API access
		// token.
		String token = signatureStarter.startWithWebPki();

		// The token acquired above can only be used for a single signature attempt. In order to
		// retry the signature it is necessary to get a new token. This can be a problem if the user
		// uses the back button of the browser, since the browser might show a cached page that we
		// rendered previously, with a now stale token. To prevent this from happening, we call the
		// method Util.setNoCacheHeaders(), which sets HTTP headers to prevent caching of the page.
		Util.setNoCacheHeaders(response);

		// Render the signature page (templates/cod-xml-signature-sign-codeh.html).
		model.addAttribute("token", token);
		model.addAttribute("userfile", userfile);
		return "cod-xml-signature-sign-codeh";
	}

	@RequestMapping(value = "/cod-xml-signature-sign-codeh", method = {RequestMethod.POST})
	public String postCompleteCodeh(
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

		// Render the signature page (templates/cod-xml-signature-sign-codeh-result.html).
		model.addAttribute("signerCert", signerCert);
		model.addAttribute("filename", filename);
		return "cod-xml-signature-sign-codeh-result";
	}

}

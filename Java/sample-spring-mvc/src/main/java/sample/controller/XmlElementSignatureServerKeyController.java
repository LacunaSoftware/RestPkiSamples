package sample.controller;

import com.lacunasoftware.restpki.*;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;
import org.springframework.web.bind.annotation.RequestParam;
import sample.Application;
import sample.util.Util;

import java.io.IOException;
import java.nio.file.Files;
import java.security.GeneralSecurityException;
import java.security.PrivateKey;
import java.security.Signature;
import java.security.cert.Certificate;
import java.util.UUID;

@Controller
public class XmlElementSignatureServerKeyController {

	@RequestMapping(value = "/xml-element-signature-server-key", method = {RequestMethod.GET})
	public String get(
		@RequestParam(value = "userfile", required = false) String userfile,
		Model model
	) throws IOException, RestException, GeneralSecurityException {

		// Read the certificate from a PKCS#12 file.
		Certificate certificate = Util.getSampleCertificateFromPKCS12();

		// Alternative option: Get the certificate from Microsoft CryptoAPI.
		//Certificate certificate = Util.getSampleCertificateFromMSCAPI();

		// Instantiate the XmlElementSignatureStarter class, responsible for receiving the signature
		// elements and start the signature process.
		XmlElementSignatureStarter signatureStarter = new XmlElementSignatureStarter(Util.getRestPkiClient());

		// Set the signer certificate. The XmlElementSignatureStarter only accepts the Base64-encoded
		// content of certificate. We use the commons-codec package to perform that transformation.
		String certBase64 = Util.convertToBase64String(certificate.getEncoded());
		signatureStarter.setSignerCertificate(certBase64);

		// Set the path of the XML to be signed, a sample Brazilian fiscal invoice pre-generated.
		signatureStarter.setXml(Util.getSampleNFePath());

		// Set the ID of the element to be signed.
		signatureStarter.setElementToSIgnId("NFe35141214314050000662550010001084271182362300");

		// Set the signature policy.
		signatureStarter.setSignaturePolicy(SignaturePolicy.NFePadraoNacional);

		// Set the security context to be used to determine trust in the certificate chain. We have
		// encapsulated the security context choice on Util.java.
		signatureStarter.setSecurityContext(Util.getSecurityContextId());

		// Call the start() method, which initiates the signature. This yields the parameters for the
		// signature using the certificate.
		ClientSideSignatureInstructions sigInstructions = signatureStarter.start();

		// Get the key form a PKCS#12 file.
		PrivateKey pkey = (PrivateKey) Util.getSampleKeyFromPKCS12();

		// Alternative option: Get the key form Microsoft CryptoAPI.
		//PrivateKey pkey = (PrivateKey) Util.getSampleKeyFromMSCAPI();

		// Initialize the signature using the parameters returned by REST PKI with the signer's key.
		Signature signature = Signature.getInstance("NONEwithRSA");
		// Note: Differently from PAdES and CAdES signatures, REST PKI provides the hash to be signed
		// instead of the data binary. For this solution, we instantiate the Signature class
		// informing to not use any hash function "NONEwithRSA" before performing the signature.
		signature.initSign(pkey);
		// Following the RSA standards (RFC 3447), we pad the ASN.1 DigestInfo object DER-encoded,
		// related to digest algorithm object on the signature. It should be padded BEFORE the
		// "toSignHash" content.
		signature.update(sigInstructions.getEncodedDigestInfo());
		// Set the toSignHash, provided by REST PKI.
		byte[] toSignHash = sigInstructions.getToSignHashRaw();
		signature.update(toSignHash, 0, toSignHash.length);
		// Perform the signature.
		byte[] sig = signature.sign();

		// Instantiate the XmlSignatureFinisher class, responsible for completing the signature
		// process.
		XmlSignatureFinisher signatureFinisher = new XmlSignatureFinisher(Util.getRestPkiClient());

		// Set the token for this signature (received on start() method above).
		signatureFinisher.setToken(sigInstructions.getToken());

		// Set the signature. The XmlElementSignatureStarter only accepts the Base64-encoded
		// signature. We also use the common-codec package to perform this transformation.
		String sigBase64 = Util.convertToBase64String(sig);
		signatureFinisher.setSignature(sigBase64);

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

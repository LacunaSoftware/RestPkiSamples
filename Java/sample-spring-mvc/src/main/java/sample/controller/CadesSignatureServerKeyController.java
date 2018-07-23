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
import java.security.GeneralSecurityException;
import java.security.PrivateKey;
import java.security.Signature;
import java.security.cert.Certificate;
import java.util.UUID;

@Controller
public class CadesSignatureServerKeyController {

	/**
	 * This action performs a PAdES signature using REST PKI and a PKCS#12 certificate. It renders
	 * the signature page.
	 */
	@RequestMapping(value = "/cades-signature-server-key", method = {RequestMethod.GET})
	public String get(
		@RequestParam(value = "userfile", required = false) String userfile,
		@RequestParam(value = "cmsfile", required = false) String cmsfile,
		Model model
	) throws IOException, RestException, GeneralSecurityException {

		// Read the certificate from a PKCS#12 file.
		Certificate certificate = Util.getSampleCertificateFromPKCS12();

		// Alternative option: Get the certificate from Microsoft CryptoAPI.
		//Certificate certificate = Util.getSampleCertificateFromMSCAPI();

		// Get an instance of the CadesSignatureStarter2 class, responsible for receiving the
		// signature elements and start the signature process.
		CadesSignatureStarter2 signatureStarter = new CadesSignatureStarter2(Util.getRestPkiClient());

		// Set the signer certificate.
		signatureStarter.setSignerCertificateRaw(certificate.getEncoded());

		// Set the signature policy.
		signatureStarter.setSignaturePolicy(SignaturePolicy.PkiBrazilAdrBasica);

		// Set the security context to be used to determine trust in the certificate chain.
		signatureStarter.setSecurityContext(SecurityContext.lacunaTest);

		// Optionally, set whether the content should be encapsulated in the resulting CMS. If this
		// parameter is omitted or set to null, the following rules apply:
		// - If no CmsToCoSign is given, the resulting CMS will include the content;
		// - If a CmsToCoSign is given, the resulting CMS will include the content if and only if
		//   the CmsToCoSign also includes the content.
		signatureStarter.setEncapsulateContent(true);

		// Below we'll either set the file to be signed or the CMS to be co-signed. Prefer passing
		// a path or a stream instead of the file's contents as a byte array to prevent memory
		// allocation issues with large files.
		if (userfile != null && !userfile.isEmpty()) {

			// If the URL argument "userfile" is filled, it means the user was redirected here by
			// UploadController (signature with file uploaded by user). We'll set the path of the file
			// to be signed, which was saved in the temporary folder by UploadController (such a file
			// would normally come from your application's database).
			signatureStarter.setFileToSign(Application.getTempFolderPath().resolve(userfile));

		} else if (cmsfile != null && !cmsfile.isEmpty()) {

			// If the URL argument "cmsfile" is filled, the user has asked to co-sign a previously
			// signed CMS. We'll set the path to the CMS to be co-signed, which was previously
			// saved in our temporary folder by the post() method on this controller. Note two
			// important things:
			//
			//   1. The CMS to be co-signed must be set using the method "setCmsToCoSign", not the
			//      method "setContentToSign" nor "setFileToSign";
			//
			//   2. Since we're creating CMSs with encapsulated content (see call to
			//      setEncapsulateContent() below), we don't need to set the content to be signed,
			//      REST PKI will get the content from the CMS being co-signed.
			signatureStarter.setCmsToCoSign(Application.getTempFolderPath().resolve(cmsfile));

		} else {

			// If both userfile and cmsfile are null, this is the "signature with server file" case.
			// We'll set the path of the file to be signed.
			signatureStarter.setFileToSign(Util.getSampleDocPath());

		}
		// Call the start() method, which initiates the signature. This yields the parameters for the
		// signature using the certificate.
		SignatureStartResult result = signatureStarter.start();

		// Get the key form a PKCS#12 file.
		PrivateKey pkey = (PrivateKey) Util.getSampleKeyFromPKCS12();

		// Alternative option: Get the key form Microsoft CryptoAPI.
		//PrivateKey pkey = (PrivateKey) Util.getSampleKeyFromMSCAPI();

		// Initialize the signature using the parameters returned by Rest PKI with the signer's key.
		Signature signature = Signature.getInstance(result.getSignatureAlgorithm());
		signature.initSign(pkey);
		// Set the toSignData, provided by Rest PKI.
		byte[] toSignData = result.getToSignDataRaw();
		signature.update(toSignData, 0, toSignData.length);
		// Perform the signature.
		byte[] sig = signature.sign();

		// Instantiate the CadesSignatureFinisher2 class, responsible for completing the signature
		// process.
		CadesSignatureFinisher2 signatureFinisher = new CadesSignatureFinisher2(Util.getRestPkiClient());

		// Set the toke, for this signature, provided by Rest PKI.
		signatureFinisher.setToken(result.getToken());

		// Set the signature.
		signatureFinisher.setSignature(sig);

		// Call the finish() method, which finalizes the signature process and returns a
		// SignatureResult object.
		SignatureResult signatureResult = signatureFinisher.finish();

		// The "certificate" field of the SignatureResult object contains information about the
		// certificate used by the user to sign the file.
		PKCertificate signerCert = signatureResult.getCertificate();

		// At this point, you'd typically store the CMS on your database. For demonstration purposes,
		// we'll store the CMS on a temporary folder and return to the page an identifier that can be
		// used to download it.

		String filename = UUID.randomUUID() + ".p7s";

		// The SignatureResult object has various methods for writing the signature file to a stream
		// (writeTo()), local file (writeToFile()), open a stream to read the content (openRead())
		// and get its contents (getContent()). For large files, avoid the method GetContent() to
		// avoid memory allocation issues.
		signatureResult.writeToFile(Application.getTempFolderPath().resolve(filename));

		// Render the signature page (templates/cades-signature-info.html).
		model.addAttribute("signerCert", signerCert);
		model.addAttribute("filename", filename);
		return "cades-signature-server-key";
	}

}

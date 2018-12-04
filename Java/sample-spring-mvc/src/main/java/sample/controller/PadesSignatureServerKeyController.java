package sample.controller;

import com.lacunasoftware.restpki.*;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;
import org.springframework.web.bind.annotation.RequestParam;
import sample.Application;
import sample.util.PadesVisualElements;
import sample.util.Util;

import javax.servlet.http.HttpServletResponse;
import java.io.IOException;
import java.security.GeneralSecurityException;
import java.security.PrivateKey;
import java.security.Signature;
import java.security.cert.Certificate;
import java.util.UUID;

@Controller
public class PadesSignatureServerKeyController {

	/**
	 * This action performs a PAdES signature using REST PKI and a PKCS#12 certificate. It renders
	 * the signature page.
	 */
	@RequestMapping(value = "/pades-signature-server-key", method = {RequestMethod.GET})
	public String get(
			@RequestParam(value = "userfile", required = false) String userfile,
			Model model
	) throws IOException, RestException, GeneralSecurityException {

		// Read the certificate from a PKCS#12 file.
		Certificate certificate = Util.getSampleCertificateFromPKCS12();

		// Alternative option: Get the certificate from Microsoft CryptoAPI.
		//Certificate certificate = Util.getSampleCertificateFromMSCAPI();

		// Get an instance of the PadesSignatureStarter2 class, responsible for receiving the
		// signature elements and start the signature process.
		PadesSignatureStarter2 signatureStarter = new PadesSignatureStarter2(Util.getRestPkiClient());

		// Set the signer certificate.
		signatureStarter.setSignerCertificateRaw(certificate.getEncoded());

		// Set the unit of measurement used to edit the pdf marks and visual representations.
		signatureStarter.setMeasurementUnits(PadesMeasurementUnits.Centimeters);

		// Set the signature policy. For this sample, we'll use the Lacuna Test PKI in order to
		// accept our test certificate used above ("Pierre de Fermat"). This security context
		// should be used FOR DEVELOPMENT PUPOSES ONLY. In production, you'll typically want one of
		// the alternatives below. For more test certificates, see:
		// https://github.com/LacunaSoftware/RestPkiSamples/blob/master/TestCertificates.md
		signatureStarter.setSignaturePolicy(SignaturePolicy.PadesBasic);
		signatureStarter.setSecurityContext(SecurityContext.lacunaTest);

		// Create a visual representation for the signature.
		signatureStarter.setVisualRepresentation(PadesVisualElements.getVisualRepresentation());

		// Below we'll either set the PDF file to be signed. Prefer passing a path or a stream
		// instead of the file's contents as a byte array to prevent memory allocation issues with
		// large files.
		if (userfile != null && !userfile.isEmpty()) {

			// If the URL argument "userfile" is filled, it means the user was redirected here by
			// UploadController (signature with file uploaded by user). We'll set the path of the file
			// to be signed, which was saved in the temporary folder by UploadController (such a file
			// would normally come from your application's database).
			signatureStarter.setPdfToSign(Application.getTempFolderPath().resolve(userfile));

		} else {

			// If both userfile is null, this is the "signature with server file" case. We'll set the
			// file to be signed by passing its path.
			signatureStarter.setPdfToSign(Util.getSampleDocPath());

		}

		// Optionally, add marks to the PDF before signing. These differ from the signature visual
		// representation in that they are actually changes done to the document prior to signing,
		// not binded to any signature. Therefore, any number of marks can be added, for instance one
		// per page, whereas there can only be one visual representation per signature. However,
		// since the marks are in reality changes to the PDF, they can only be added to documents
		// which have no previous signatures, otherwise such signatures would be made invalid by the
		// changes to the document (see field bypassMarksIfSigned of PadesSignatureStarter2). This
		// problem does not occur with signature visual representations.
		//
		// We have encapsulated this code in a method to include several possibilities depending on
		// the argument passed. Experiment changing the argument to see different examples of PDF
		// marks (valid values are 1-4). Once you decide which is best for your case, you can place
		// the code directly here.
		//signatureStarter.addPdfMark(PadesVisualElements.getPdfMark(1));

		// Call the start() method, which initiates the signature. This yields the parameters for
		// the signature using the certificate.
		SignatureStartResult result = signatureStarter.start();

		// Get the key from a PKCS#12 file.
		PrivateKey pkey = (PrivateKey) Util.getSampleKeyFromPKCS12();

		// Alternative option: Get the key from Microsoft CryptoAPI.
		//PrivateKey pkey = (PrivateKey) Util.getSampleKeyFromMSCAPI();

		// Initialize  the signature using the parameters returned by Rest PKI with the signer's key.
		Signature signature = Signature.getInstance(result.getSignatureAlgorithm());
		signature.initSign(pkey);
		// Set the toSignData, provided by Rest PKI.
		byte[] toSignData = result.getToSignDataRaw();
		signature.update(toSignData, 0, toSignData.length);
		// Perform the signature.
		byte[] sig = signature.sign();

		//
		// Get an instance of the PadesSignatureFinisher2 class, responsible for completing the
		// signature process.
		PadesSignatureFinisher2 signatureFinisher = new PadesSignatureFinisher2(Util.getRestPkiClient());

		// Set the token for this signature, provided by Rest PKI.
		signatureFinisher.setToken(result.getToken());

		// Set the signature
		signatureFinisher.setSignature(sig);

		// Call the finish() method, which finalizes the signature process and returns a
		// SignatureResult object.
		SignatureResult signatureResult = signatureFinisher.finish();

		// The "certificate" field of the SignatureResult object contains information about the
		// certificate used by the user to sign the file.
		PKCertificate signerCert = signatureResult.getCertificate();

		// At this point, you'd typically store the signed PDF on your database. For demonstration
		// purposes, we'll store the PDF on a temporary folder and return to the page an identifier
		// that can be used to download it.

		String filename = UUID.randomUUID() + ".pdf";

		// The SignatureResult object has various methods for writing the signature file to a stream
		// (writeTo()), local file (writeToFile()), open a stream to read the content (openRead())
		// and get its contents (getContent()). For large files, avoid the method getContent() to
		// avoid memory allocation issues.
		signatureResult.writeToFile(Application.getTempFolderPath().resolve(filename));

		// Render the signature page (templates/pades-signature-info.html).
		model.addAttribute("signerCert", signerCert);
		model.addAttribute("filename", filename);
		return "pades-signature-server-key";
	}
}

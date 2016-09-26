package sample.controller;

import com.lacunasoftware.restpki.*;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.*;
import sample.Application;
import sample.controller.util.PadesVisualElements;
import sample.util.*;

import javax.servlet.http.HttpServletResponse;
import java.io.IOException;
import java.nio.file.Files;
import java.util.UUID;

@Controller
public class PadesSignatureController {

	/*
	 * This action initiates a PAdES signature using REST PKI and renders the signature page.
	 *
	 * Both PAdES signature examples, with a server file and with a file uploaded by the user, converge to this action.
	 * The difference is that, when the file is uploaded by the user, the action is called with a URL argument named
	 * "userfile".
	 */
	@RequestMapping(value = "/pades-signature", method = {RequestMethod.GET})
	public String get(
		@RequestParam(value = "userfile", required = false) String userfile,
		Model model,
		HttpServletResponse response
	) throws IOException, RestException {

		// Instantiate the PadesSignatureStarter class, responsible for receiving the signature elements and start the
		// signature process. For more information, see:
		// https://pki.rest/Content/docs/java-client/index.html?com/lacunasoftware/restpki/PadesSignatureStarter.html
		PadesSignatureStarter signatureStarter = new PadesSignatureStarter(Util.getRestPkiClient());

		if (userfile != null && !userfile.isEmpty()) {

			// If the URL argument "userfile" is filled, it means the user was redirected here by UploadController
			// (signature with file uploaded by user). We'll set the path of the file to be signed, which was saved in the
			// temporary folder by UploadController (such a file would normally come from your application's database)
			signatureStarter.setPdfToSign(Application.getTempFolderPath().resolve(userfile));

		} else {

			// If both userfile is null, this is the "signature with server file" case. We'll set the file to
			// be signed as a byte array
			signatureStarter.setPdfToSign(Util.getSampleDocContent());

		}

		// Set the unit of measurement used to edit the pdf marks and visual representations
		signatureStarter.setMeasurementUnits(PadesMeasurementUnits.Centimeters);

		// Set the signature policy
		signatureStarter.setSignaturePolicy(SignaturePolicy.PadesBasic);

		// Set a SecurityContext to be used to determine trust in the certificate chain
		signatureStarter.setSecurityContext(SecurityContext.pkiBrazil);
		// Note: By changing the SecurityContext above you can accept only certificates from a certain PKI,
		// for instance, ICP-Brasil (SecurityContext.pkiBrazil).

		// Create a visual representation for the signature
		PadesVisualRepresentation visualRepresentation = new PadesVisualRepresentation();

		// Set the text that will be inserted in the signature visual representation with the date time of the signature.
		// The tags {{signerName}} and {{signerNationalId}} will be substituted according to the user's certificate
		// signerName       -> full name of the signer
		// signerNationalId -> if the certificate is ICP-Brasil, contains the signer's CPF
		PadesVisualText text = new PadesVisualText("Assinado por {{signerName}} ({{signerNationalId}})", true);
		// Optionally set the text horizontal alignment (Left or Right), if not set the default is Left.
		text.setHorizontalAlign(PadesTextHorizontalAlign.Left);
		visualRepresentation.setText(text);

		// Set a image to stamp the signature visual representation
		visualRepresentation.setImage(new PadesVisualImage(Util.getPdfStampContent(), "image/png"));

		// Position of the visual representation. We have encapsulated this code in a method to include several
		// possibilities depending on the argument passed to the function. Experiment changing the argument to see
		// different examples of signature positioning (valid values are 1-6). Once you decide which is best for your
		// case, you can place the code directly here.
		visualRepresentation.setPosition(PadesVisualElements.getVisualRepresentationPosition(4));

		// Set the visual representation created
		signatureStarter.setVisualRepresentation(visualRepresentation);

		// Optionally, add marks to the PDF before signing. These differ from the signature visual representation in that
		// they are actually changes done to the document prior to signing, not binded to any signature. Therefore, any number
		// of marks can be added, for instance one per page, whereas there can only be one visual representation per signature.
		// However, since the marks are in reality changes to the PDF, they can only be added to documents which have no previous
		// signatures, otherwise such signatures would be made invalid by the changes to the document (see property
		// PadesSignatureStarter.bypassMarksIfSigned). This problem does not occurr with signature visual representations.

		// We have encapsulated this code in a method to include several possibilities depending on the argument passed.
		// Experiment changing the argument to see different examples of PDF marks. Once you decide which is best for your case,
		// you can place the code directly here.
		// signatureStarter.addPdfMark(PadesVisualElements.getPdfMark(1));

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

		// Render the signature page (templates/pades-signature.html)
		model.addAttribute("token", token);
		model.addAttribute("userfile", userfile);
		return "pades-signature";
	}

	/*
	 * This action receives the form submission from the signature page. We'll call REST PKI to complete the signature.
	 */
	@RequestMapping(value = "/pades-signature", method = {RequestMethod.POST})
	public String post(
		@RequestParam(value = "token", required = true) String token,
		Model model
	) throws IOException, RestException {

		// Instantiate the PadesSignatureFinisher class, responsible for completing the signature process. For more
		// information, see:
		// https://pki.rest/Content/docs/java-client/index.html?com/lacunasoftware/restpki/PadesSignatureFinisher.html
		PadesSignatureFinisher signatureFinisher = new PadesSignatureFinisher(Util.getRestPkiClient());

		// Set the token for this signature (rendered in a hidden input field, see file templates/pades-signature.html)
		signatureFinisher.setToken(token);

		// Call the finish() method, which finalizes the signature process and returns the signed PDF's bytes
		byte[] signedPdf = signatureFinisher.finish();

		// Get information about the certificate used by the user to sign the file. This method must only be called after
		// calling the finish() method.
		PKCertificate signerCert = signatureFinisher.getCertificateInfo();

		// At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
		// store the PDF on a temporary folder and return to the page an identifier that can be used to download it.

		String filename = UUID.randomUUID() + ".pdf";
		Files.write(Application.getTempFolderPath().resolve(filename), signedPdf);
		model.addAttribute("signerCert", signerCert);
		model.addAttribute("filename", filename);
		return "pades-signature-info";
	}
}

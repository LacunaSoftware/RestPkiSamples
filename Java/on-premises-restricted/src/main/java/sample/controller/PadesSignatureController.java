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
public class PadesSignatureController {

	/*
	 * This action renders the page for the first step of the signature process, on which the user will choose the
	 * certificate to be used to sign the file and we'll programmatically retrieve the certificate's encoding using
	 * the Web PKI component and send back to the server.
	 *
	 * Both PAdES signature examples, with a server file and with a file uploaded by the user, converge to this action.
	 * The difference is that, when the file is uploaded by the user, the action is called with a URL argument named
	 * "userfile".
	 */
	@RequestMapping(value = "/pades-signature", method = {RequestMethod.GET})
	public String get(
		@RequestParam(value = "userfile", required = false) String userfile,
		Model model
	) throws IOException, RestException {
		model.addAttribute("userfile", userfile);
		// Render the page for the first step of the signature process, on which the user chooses the certificate and
		// its encoding is read (templates/pades-signature-step1.html)
		return "pades-signature-step1";
	}

	/*
	 * This action receives the encoding of the certificate chosen by the user, uses it to initiate a PAdES signature
	 * using REST PKI and renders the page for the final step of the signature process.
    */
	@RequestMapping(value = "/pades-signature", method = {RequestMethod.POST})
	public String post(
		@RequestParam(value = "selectedCertThumb", required = true) String selectedCertThumb,
		@RequestParam(value = "certificate", required = true) String certificate,
		@RequestParam(value = "userfile", required = false) String userfile,
		Model model,
		HttpServletResponse response
	) throws IOException, RestException {

		// Instantiate the PadesSignatureStarter class, responsible for receiving the signature elements and start the
		// signature process. For more information, see:
		// https://pki.rest/Content/docs/java-client/index.html?com/lacunasoftware/restpki/PadesSignatureStarter.html
		PadesSignatureStarter signatureStarter = new PadesSignatureStarter(Util.getRestPkiClient());

		if (userfile != null && !userfile.isEmpty()) {

			// If the URL argument "userfile" is filled, it means the user was redirected to the get() method (above) by
			// UploadController (signature with file uploaded by user). We'll set the path of the file to be signed, which
			// was saved in the temporary folder by UploadController (such a file would normally come from your
			// application's database)
			signatureStarter.setPdfToSign(Application.getTempFolderPath().resolve(userfile));

		} else {

			// If userfile is null, this is the "signature with server file" case.
			signatureStarter.setPdfToSign(Util.getSampleDocContent());

		}

		// Set the certificate's encoding in base64 encoding (which is what the Web PKI component yields)
		signatureStarter.setSignerCertificate(certificate);

		// Set the signature policy
		signatureStarter.setSignaturePolicy(SignaturePolicy.PadesBasic);

		// Set a SecurityContext to be used to determine trust in the certificate chain
		//signatureStarter.setSecurityContext(SecurityContext.pkiBrazil);
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
		visualRepresentation.setPosition(getVisualRepresentationPosition(4));

		// Set the visual representation created
		signatureStarter.setVisualRepresentation(visualRepresentation);

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
			String retryUrl = "/pades-signature";
			if (userfile != null && !userfile.isEmpty()) {
				retryUrl += "?userfile=" + userfile;
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
		model.addAttribute("userfile", userfile);
		return "pades-signature-step2";
	}

	// This method is called by the get() method. It contains examples of signature visual representation positionings.
	private PadesVisualPositioning getVisualRepresentationPosition(int sampleNumber) throws RestException {

		switch (sampleNumber) {

			case 1:
				// Example #1: automatic positioning on footnote. This will insert the signature, and future signatures,
				// ordered as a footnote of the last page of the document
				return PadesVisualPositioning.getFootnote(Util.getRestPkiClient());

			case 2:
				// Example #2: get the footnote positioning preset and customize it
				PadesVisualAutoPositioning footnotePosition = PadesVisualPositioning.getFootnote(Util.getRestPkiClient());
				footnotePosition.getContainer().setBottom(3.0);
				return footnotePosition;

			case 3:
				// Example #3: manual positioning
				PadesVisualRectangle pos = new PadesVisualRectangle();
				pos.setWidthLeftAnchored(5.0, 2.54);
				pos.setHeightBottomAnchored(3.0, 2.54);
				// The first parameter is the page number. Negative numbers represent counting from end of the document
				// (-1 is last page)
				return new PadesVisualManualPositioning(-1, PadesMeasurementUnits.Centimeters, pos);

			case 4:
				// Example #4: auto positioning
				PadesVisualRectangle container = new PadesVisualRectangle();
				container.setHorizontalStretch(2.54, 2.54);
				container.setHeightBottomAnchored(12.31, 2.54);
				return new PadesVisualAutoPositioning(-1, PadesMeasurementUnits.Centimeters, container, new PadesSize(5.0, 3.0), 1.0);

			default:
				return null;
		}
	}

	/*
	 * This action receives the form submission from the page for the final step of the signature process. We'll call
	 * REST PKI to complete the signature.
	 */
	@RequestMapping(value = "/pades-signature-complete", method = {RequestMethod.POST})
	public String complete(
		@RequestParam(value = "token", required = true) String token,
		@RequestParam(value = "signature", required = true) String signature,
		Model model
	) throws IOException, RestException {

		// Instantiate the PadesSignatureFinisher class, responsible for completing the signature process. For more
		// information, see:
		// https://pki.rest/Content/docs/java-client/index.html?com/lacunasoftware/restpki/PadesSignatureFinisher.html
		PadesSignatureFinisher signatureFinisher = new PadesSignatureFinisher(Util.getRestPkiClient());

		// Set the token for this signature (rendered in a hidden input field, see file templates/pades-signature.html)
		signatureFinisher.setToken(token);

		// Set the result of the signature operation
		signatureFinisher.setSignature(signature);

		// Call the finish() method, which finalizes the signature process and returns the signed PDF's bytes
		byte[] signedPdf;
		try {
			signedPdf = signatureFinisher.finish();
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

		// At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
		// store the PDF on a temporary folder and return to the page an identifier that can be used to download it.

		String filename = UUID.randomUUID() + ".pdf";
		Files.write(Application.getTempFolderPath().resolve(filename), signedPdf);
		model.addAttribute("signerCert", signerCert);
		model.addAttribute("filename", filename);
		return "pades-signature-info";
	}
}

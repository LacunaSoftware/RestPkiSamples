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

	/**
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

		// Instantiate the PadesSignatureStarter2 class, responsible for receiving the signature elements and start the
		// signature process.
		PadesSignatureStarter2 signatureStarter = new PadesSignatureStarter2(Util.getRestPkiClient());

		// Set the unit of measurement used to edit the pdf marks and visual representations.
		signatureStarter.setMeasurementUnits(PadesMeasurementUnits.Centimeters);

		// Set the signature policy.
		signatureStarter.setSignaturePolicy(SignaturePolicy.PadesBasicWithPkiBrazilCerts);
		// Note: Depending on the signature policy chosen above, setting the security context below may be
		// mandatory (this is not the case for ICP-Brasil policies, which will automatically use the PkiBrazil security
		// context if none is passed)

		// Alternative option: add a ICP-Brasil timestamp to the signature
		//signatureStarter.setSignaturePolicy(SignaturePolicy.PadesTWithPkiBrazilCerts);

		// Alternative option: PAdES Basic with PKIs trusted by Windows.
		//signatureStarter.setSignaturePolicy(SignaturePolicy.PadesBasic);
		//signatureStarter.setSecurityContext(SecurityContext.windowsServer);

		// Alternative option: PAdES Basic with a custom security context containing, for instance, your private PKI
		// certificate
		//signatureStarter.setSignaturePolicy(SignaturePolicy.PadesBasic);
		//signatureStarter.setSecurityContext(new SecurityContext("ID OF YOUR CUSTOM SECURITY CONTEXT"));

		// Create a visual representation for the signature
		PadesVisualRepresentation visualRepresentation = new PadesVisualRepresentation();

		// The tags {{name}} and {{br_cpf_formatted}} will be substituted according to the user's certificate
		//
		//		name             : full name of the signer
		//		br_cpf_formatted : if the certificate is ICP-Brasil, contains the signer's CPF
		//
		// For a full list of the supported tags, see: https://github.com/LacunaSoftware/RestPkiSamples/blob/master/PadesTags.md
		PadesVisualText text = new PadesVisualText("Assinado por {{signerName}} ({{signerNationalId}})", true);
		// Optionally set the text horizontal alignment (Left or Right), if not set the default is Left.
		text.setHorizontalAlign(PadesTextHorizontalAlign.Left);
		// Add to the visual representation
		visualRepresentation.setText(text);

		// We'll use as background the image in resources/static/PdfStamp.png
		PadesVisualImage image = new PadesVisualImage(Util.getPdfStampContent(), "image/png");
		// Opacity is an integer from 0 to 100 (0 is completely transparent, 100 is completely opaque).
		image.setOpacity(50);
		// Align the image to the right
		image.setHorizontalAlign(PadesHorizontalAlign.Right);
		// Add to visual representation
		visualRepresentation.setImage(image);

		// Position of the visual representation. We have encapsulated this code in a method to include several
		// possibilities depending on the argument passed to the function. Experiment changing the argument to see
		// different examples of signature positioning (valid values are 1-6). Once you decide which is best for your
		// case, you can place the code directly here.
		visualRepresentation.setPosition(PadesVisualElements.getVisualRepresentationPosition(1));

		// Set the visual representation created
		signatureStarter.setVisualRepresentation(visualRepresentation);

		// Below we'll either set the PDF file to be signed. Prefer passing a path or a stream instead of the file's
		// contents as a byte array to prevent memory allocation issues with large files.
		if (userfile != null && !userfile.isEmpty()) {

			// If the URL argument "userfile" is filled, it means the user was redirected here by UploadController
			// (signature with file uploaded by user). We'll set the path of the file to be signed, which was saved in
			// the temporary folder by UploadController (such a file would normally come from your application's
			// database)
			signatureStarter.setPdfToSign(Application.getTempFolderPath().resolve(userfile));

		} else {

			// If both userfile is null, this is the "signature with server file" case. We'll set the file to be signed
			// as a byte array
			signatureStarter.setPdfToSign(Util.getSampleDocPath());

		}

		/*
			Optionally, add marks to the PDF before signing. These differ from the signature visual representation in
			that they are actually changes done to the document prior to signing, not binded to any signature.
			Therefore, any number of marks can be added, for instance one per page, whereas there can only be one visual
			representation per signature. However, since the marks are in reality changes to the PDF, they can only be
			added to documents which have no previous signatures, otherwise such signatures would be made invalid by the
			changes to the document (see property PadesSignatureStarter.bypassMarksIfSigned). This problem does not
			occur with signature visual representations.

			We have encapsulated this code in a method to include several possibilities depending on the argument
			passed. Experiment changing the argument to see different examples of PDF marks. Once you decide which is
			best for your case, you can place the code directly here.
		 */
		// signatureStarter.addPdfMark(PadesVisualElements.getPdfMark(1));

		// Call the startWithWebPki() method, which initiates the signature. This yields a SignatureStartWithWebPkiResult
		// object containing the signer certificate and the token, a 43-character case-sensitive URL-safe string, which
		// identifies this signature process. We'll use this value to call the signWithRestPki() method on the Web PKI
		// component (see file static/js/signature-form.js) and also to complete the signature after the form
		// is submitted (see method complete() below). This should not be mistaken with the API access token.
		SignatureStartWithWebPkiResult result = signatureStarter.startWithWebPki();

		// The token acquired above can only be used for a single signature attempt. In order to retry the signature it
		// is necessary to get a new token. This can be a problem if the user uses the back button of the browser, since
		// the browser might show a cached page that we rendered previously, with a now stale token. To prevent this
		// from happening, we call the method Util.setNoCacheHeaders(), which sets HTTP headers to prevent caching of
		// the page.
		Util.setNoCacheHeaders(response);

		// Render the signature page (templates/pades-signature.html)
		model.addAttribute("token", result.getToken());
		model.addAttribute("userfile", userfile);
		return "pades-signature";
	}

	/**
	 * This action receives the form submission from the signature page. We'll call REST PKI to complete the signature.
	 */
	@RequestMapping(value = "/pades-signature", method = {RequestMethod.POST})
	public String post(
		@RequestParam(value = "token", required = true) String token,
		Model model
	) throws IOException, RestException {

		// Instantiate the PadesSignatureFinisher2 class, responsible for completing the signature process.
		PadesSignatureFinisher2 signatureFinisher = new PadesSignatureFinisher2(Util.getRestPkiClient());

		// Set the token for this signature (rendered in a hidden input field, see file templates/pades-signature.html)
		signatureFinisher.setToken(token);

		// Call the finish() method, which finalizes the signature process and returns a SignatureResult object.
		SignatureResult signatureResult = signatureFinisher.finish();

		// The "Certificate" property of the SignatureResult object contains information about the certificate used by
		// the user to sign the file.
		PKCertificate signerCert = signatureResult.getCertificate();

		// At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
		// store the PDF on a temporary folder and return to the page an identifier that can be used to download it.

		String filename = UUID.randomUUID() + ".pdf";
		// The SignatureResult object has various methods for writing the signature file to a stream (writeTo()), local
		// file (writeToFile()), open a stream to read the content (openRead()) and get its contents (getContent()). For
		// large files, avoid the method getContent() to avoid memory allocation issues.
		signatureResult.writeToFile(Application.getTempFolderPath().resolve(filename));

		// Render the signature page (templates/pades-signature-info.html)
		model.addAttribute("signerCert", signerCert);
		model.addAttribute("filename", filename);
		return "pades-signature-info";
	}
}

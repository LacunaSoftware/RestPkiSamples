package sample.controller;

import com.lacunasoftware.restpki.*;
import org.springframework.web.bind.annotation.*;
import sample.models.*;
import sample.util.*;

import javax.servlet.http.HttpServletRequest;
import java.io.IOException;

/**
 * This controller contains the server-side logic for the PAdES signature example. The client-side is implemented at:
 * - View: src/main/resources/templates/pades-signature.html
 * - JS: src/main/resources/static/js/app/pades-signature.js
 * <p>
 * This controller uses the classes PadesSignatureStarter and PadesSignatureFinisher of the REST PKI Java client lib.
 * For more information, see:
 * https://restpki.lacunasoftware.com/Content/docs/java-client/index.html?com/lacunasoftware/restpki/PadesSignatureStarter.html
 * https://restpki.lacunasoftware.com/Content/docs/java-client/index.html?com/lacunasoftware/restpki/PadesSignatureFinisher.html
 */
@RestController
public class PadesSignatureController {

	/**
	 * POST api/pades-signature/start
	 * <p>
	 * This action is called by the page to initiate the signature process, after reading the user's certificate.
	 */
	@RequestMapping(value = "/api/pades-signature/start", method = {RequestMethod.POST})
	public SignatureStartResponse start(@RequestBody SignatureStartRequest request) throws IOException, RestException {

		// Instantiate the PadesSignatureStarter class, responsible for receiving the signature elements and start the
		// signature process
		PadesSignatureStarter signatureStarter = new PadesSignatureStarter(Util.getRestPkiClient());

		// Set the PDF to be signed, which in the case of this example is a fixed sample document
		signatureStarter.setPdfToSign(Util.getSampleDocContent());

		// Set the signer certificate (encoded in Base64, as outputted by the Web PKI component).
		signatureStarter.setSignerCertificate(request.getCertificate());

		// Set the signature policy
		signatureStarter.setSignaturePolicy(SignaturePolicy.PadesBasic);

		// Set a SecurityContext to be used to determine trust in the certificate chain
		signatureStarter.setSecurityContext(new SecurityContext("d1299963-d8cb-46a0-930e-10e0fdcff7fb"));
		// >>> NOTE: By changing the SecurityContext above you can accept only certificates from a certain PKI,
		// for instance ICP-Brasil (SecurityContext.pkiBrazil). You can also define a custom security context on the
		// REST PKI website accepting whatever root certification authorities you wish.

		// Create a visual representation for the signature
		PadesVisualRepresentation visualRepresentation = new PadesVisualRepresentation();

		// Set the text that will be inserted in the signature visual representation with the date time of the signature.
		// The tags {{signerName}} and {{signerNationalId}} will be replaced by its real values present in the signer certificate
		visualRepresentation.setText(new PadesVisualText("Assinado por {{signerName}} (CPF {{signerNationalId}})", true));

		// Set a image to stamp the signature visual representation
		visualRepresentation.setImage(new PadesVisualImage(Util.getPdfStampContent(), "image/png"));

		// Set the position that the visual representation will be inserted in the document. Changing
		// the number below will result in different examples of signature positioning being used.
		visualRepresentation.setPosition(getVisualRepresentationPosition(4));

		// Set the visual representation created
		signatureStarter.setVisualRepresentation(visualRepresentation);

		SignatureStartResponse response = new SignatureStartResponse();
		ClientSideSignatureInstructions signatureInstructions;

		try {

			// Call the start() method, which initiates the signature. This yields an instance of the
			// ClientSideSignatureInstructions class, which contains the parameters needed to perform the signature
			// on the client-side.
			signatureInstructions = signatureStarter.start();

		} catch (ValidationException e) {

			// The start() method may throw a ValidationException, for instance if the certificate is expired
			response.setSuccess(false);
			response.setMessage("The validation of the certificate has failed");
			response.setValidationResults(e.getValidationResults().toString());
			return response;

		}

		// return the parameters needed to perform the signature on the client-side
		response.setSuccess(true);
		response.setToken(signatureInstructions.getToken());
		response.setToSignHash(signatureInstructions.getToSignHash());
		response.setDigestAlgorithmOid(signatureInstructions.getDigestAlgorithmOid());
		return response;
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

	/**
	 * POST api/pades-signature/complete
	 * <p>
	 * This action is called once the signature is complete on the client-side. The page sends back on the URL the token
	 * originally yielded by the get() method.
	 */
	@RequestMapping(value = "/api/pades-signature/complete", method = {RequestMethod.POST})
	public SignatureCompleteResponse complete(HttpServletRequest httpRequest, @RequestBody SignatureCompleteRequest request) throws IOException, RestException {

		// Instantiate the PadesSignatureFinisher class, responsible for completing the signature process
		PadesSignatureFinisher signatureFinisher = new PadesSignatureFinisher(Util.getRestPkiClient());

		// Set the token previously yielded by the start() method (which we sent to the page and the page
		// sent us back on the request)
		signatureFinisher.setToken(request.getToken());

		// Set the signature computed on the client-side
		signatureFinisher.setSignature(request.getSignature());

		SignatureCompleteResponse response = new SignatureCompleteResponse();
		byte[] signedPdf;

		try {

			// Call the finish() method, which finalizes the signature process. Unlike the complete() method of the
			// Authentication class, this method throws an exception if validation of the signature fails.
			signedPdf = signatureFinisher.finish();

		} catch (ValidationException e) {

			// If validation of the signature failed, inform the page
			response.setSuccess(false);
			response.setMessage("The validation of the signature has failed");
			response.setValidationResults(e.getValidationResults().toString());
			return response;

		}

		// At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
		// store the PDF on a temporary folder and return to the page an ID that can be used to open the signed PDF.
		DatabaseMock dbMock = new DatabaseMock(httpRequest.getSession());
		String signatureId = dbMock.putSignedPdf(signedPdf);

		response.setSuccess(true);
		response.setSignatureId(signatureId);

		return response;
	}

}

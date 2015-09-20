package sample.controller;

import com.lacunasoftware.restpki.*;
import org.springframework.web.bind.annotation.*;
import sample.models.*;
import sample.util.*;

import javax.servlet.http.HttpServletRequest;
import java.io.IOException;

/**
 * This controller contains the server-side logic for the PAdES signature example. The client-side is implemented at:
 * - HTML: src/main/resources/templates/padesSignature.html
 * - JS: src/main/resources/static/js/app/pades-signature.js
 *
 * This controller implements the logic described at
 * http://pki.lacunasoftware.com/Help/html/c5494b89-d573-4a35-a911-721e32b08dd9.htm
 *
 */
@RestController
public class PadesSignatureController {

    /**
     * GET api/pades-signature
     *
     * This action is called once the user's certificate encoding has been read, and contains the
     * logic to prepare the byte array, encoded in base64, that needs to be actually signed with the user's private key
     * (the "to-sign-bytes").
     */
    @RequestMapping(value = "/api/pades-signature", method = {RequestMethod.GET})
    public String start() throws IOException, RestException {

        RestPkiClient client = new RestPkiClient(Util.getRestPkiEndpoint(), Util.getAuthToken());

        // Instantiate the PadesSignatureStarter class, responsible for receiving the signature elements and star the
        // signature process
        PadesSignatureStarter signatureStarter = new PadesSignatureStarter(client);

        // Set the PDF to sign, which in the case of this example is a fixed sample document
        signatureStarter.setPdfToSign(Util.getSampleDocContent());

        // Set the signature policy
        signatureStarter.setSignaturePolicy(SignaturePolicy.PadesBasic);

        // Set a SecurityContext to be used to determine trust in the certificate chain
        signatureStarter.setSecurityContext(Util.getSecurityContext());
        // Note: By changing the SecurityContext above you can accept only certificates from a certain PKI,
        // for instance, ICP-Brasil (SecurityContext.pkiBrazil).

        // Create a visual representation for the signature
        PadesVisualRepresentation visualRepresentation = new PadesVisualRepresentation();

        // Set the text that will be inserted in the signature visual representation with the date time of the signature.
        // The tags {{signerName}} and {{signerNationalId}} will be replaced by its real values present in the signer certificate
        visualRepresentation.setText(new PadesVisualText("Assinado por {{signerName}} (CPF {{signerNationalId}})", true));

        // Set a image to stamp the signature visual representation
        visualRepresentation.setImage(new PadesVisualImage(Util.getPdfStampContent(), "image/png"));

        // Set the position that the visual representation will be inserted in the document. The getFootnot() its an
        // auto positioning preset. It will insert the signature, and future signatures, ordered in the footnote of the document
        visualRepresentation.setPosition(PadesVisualPositioning.getFootnote(client));

        // Set the visual representation created
        signatureStarter.setVisualRepresentation(visualRepresentation);

        String token = signatureStarter.startWithWebPki();

        return token;
    }

    /**
     * POST api/pades-signature
     *
     * This action is called once the "to-sign-bytes" are signed using the user's certificate. The
     * page sends back the SignatureProcess ID and the signature operation result.
     */
    @RequestMapping(value = "/api/pades-signature", method = {RequestMethod.POST})
    public SignatureCompleteResponse complete(HttpServletRequest httpRequest, @RequestParam(value="token", required=true) String token) throws IOException, RestException {

        RestPkiClient client = new RestPkiClient(Util.getRestPkiEndpoint(), Util.getAuthToken());
        PadesSignatureFinisher signatureFinisher = new PadesSignatureFinisher(client);
        signatureFinisher.setToken(token);

        SignatureCompleteResponse response = new SignatureCompleteResponse();

        try {

            signatureFinisher.finish();

        } catch (ValidationException e) {

            response.setSuccess(false);
            response.setMessage("A validation error has occurred");
            response.setValidationResults(e.getValidationResults().toString());
            return response;

        }

        DatabaseMock dbMock = new DatabaseMock(httpRequest.getSession());
        String signatureId = dbMock.putSignedPdf(signatureFinisher.getSignedPdf());

        response.setSuccess(true);
        response.setSignatureId(signatureId);

        return response;
    }

}

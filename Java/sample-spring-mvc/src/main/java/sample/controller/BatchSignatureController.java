package sample.controller;

import com.lacunasoftware.restpki.*;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.*;

import javax.servlet.http.HttpServletResponse;
import java.io.IOException;
import java.nio.file.Files;
import java.util.ArrayList;
import java.util.List;
import java.util.UUID;

import sample.Application;
import sample.controller.util.PadesVisualElements;
import sample.util.*;

@Controller
public class BatchSignatureController {
    /*
     * This action renders the batch signature page.
     *
     * Notice that the only thing we'll do on the server-side at this point is determine the IDs of the documents
     * to be signed. The page will handle each document one by one and will call the server asynchronously to
     * start and complete each signature.
     */
    @RequestMapping(value = "/batch-signature", method = {RequestMethod.GET})
    public String get(
            Model model,
            HttpServletResponse response
    ) {
        // It is up to your application's business logic to determine which documents will compose the batch
        List<Integer> lst = new ArrayList<Integer>();
        for (int i = 1; i < 31; i++) {
            lst.add(i);
        }
        model.addAttribute("documentIds", lst);

        return "batch-signature";
    }

    /**
     * This action is called asynchronously from the batch signature page in order to initiate the signature of each document
     * in the batch.
     */
    @RequestMapping(value = "/batch-signature-start", method = {RequestMethod.POST})
    public @ResponseBody String start(
            @RequestParam(value = "id", required = true) int id,
            Model model,
            HttpServletResponse response
    ) throws IOException, RestException {
        // Get an instance of the PadesSignatureStarter class, responsible for receiving the signature elements and start the
        // signature process
        PadesSignatureStarter signatureStarter = new PadesSignatureStarter(Util.getRestPkiClient());

        // Set the unit of measurement used to edit the pdf marks and visual representations
        signatureStarter.setMeasurementUnits(PadesMeasurementUnits.Centimeters);

        // Set the signature policy
        signatureStarter.setSignaturePolicy(SignaturePolicy.PadesBasic);

        // Set a SecurityContext to be used to determine trust in the certificate chain
        //signatureStarter.setSecurityContext(SecurityContext.pkiBrazil);
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

        // Set the document to be signed based on its ID (passed to us from the page)
        signatureStarter.setPdfToSign(Util.getBatchDocContent(id));

        // Call the startWithWebPki() method, which initiates the signature. This yields the token, a 43-character
        // case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
        // signWithRestPki() method on the Web PKI component (see file signature-form.js) and also to complete the
        // signature after the form is submitted (see method post() below). This should not be mistaken with the API
        // access token.
        String token = signatureStarter.startWithWebPki();

        // Return a JSON with the token obtained from REST PKI (the page will use jQuery to decode this value)
        return "\"" + token + "\"";
    }

    /**
     * This action receives the form submission from the view. We'll call REST PKI to complete the signature.
     *
     * Notice that the "id" is actually the signature process token. We're naming it "id" so that the action
     * can be called as /BatchSignature/Complete/{token}
     */
    @RequestMapping(value = "/batch-signature-complete", method = {RequestMethod.POST})
    public @ResponseBody String complete(
            @RequestParam(value = "token", required = true) String token,
            Model model,
            HttpServletResponse response
    ) throws IOException, RestException {
        // Instantiate the PadesSignatureFinisher class, responsible for completing the signature process. For more
        // information, see:
        // https://pki.rest/Content/docs/java-client/index.html?com/lacunasoftware/restpki/PadesSignatureFinisher.html
        PadesSignatureFinisher signatureFinisher = new PadesSignatureFinisher(Util.getRestPkiClient());

        // Set the token for this signature (rendered in a hidden input field, see file templates/pades-signature.html)
        signatureFinisher.setToken(token);

        // Call the finish() method, which finalizes the signature process and returns the signed PDF's bytes
        byte[] signedPdf = signatureFinisher.finish();

        // At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
        // store the PDF on a temporary folder and return to the page an identifier that can be used to download it.
        String filename = UUID.randomUUID() + ".pdf";
        Files.write(Application.getTempFolderPath().resolve(filename), signedPdf);

        return "\"" + filename + "\"";
    }
}

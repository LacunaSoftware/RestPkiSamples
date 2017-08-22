package sample.controller;

import com.lacunasoftware.restpki.*;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.*;

import javax.servlet.http.HttpServletResponse;
import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.util.ArrayList;
import java.util.List;
import java.util.UUID;

import sample.Application;
import sample.controller.util.PadesVisualElements;
import sample.util.*;

@Controller
public class BatchSignatureController {

    /**
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
     * This action is called asynchronously from the batch signature page in order to initiate the signature of each
     * document in the batch.
     */
    @RequestMapping(value = "/batch-signature-start", method = {RequestMethod.POST})
    public @ResponseBody String start(
            @RequestParam(value = "id", required = true) int id,
            Model model,
            HttpServletResponse response
    ) throws IOException, RestException {

        // Get an instance of the PadesSignatureStarter2 class, responsible for receiving the signature elements and
        // start the signature process
        PadesSignatureStarter2 signatureStarter = new PadesSignatureStarter2(Util.getRestPkiClient());

        // Set the unit of measurement used to edit the pdf marks and visual representations
        signatureStarter.setMeasurementUnits(PadesMeasurementUnits.Centimeters);

        // Set the signature policy
        signatureStarter.setSignaturePolicy(SignaturePolicy.PadesBasicWithPkiBrazilCerts);
        // Note: Depending on the signature policy chosen above, setting the security context below may be
        // mandatory (this is not the case for ICP-Brasil policies, which will automatically use the PkiBrazil security
        // context if none is passed)

        // Optionally, set a security context to be used to determine trust in the certificate chain
        //signatureStarter.setSecurityContext(new SecurityContext("ID OF YOUR CUSTOM SECURITY CONTEXT"));

        // For instance, to use the test certificates on Lacuna Test PKI (for development purposes only!):
        //signatureStarter.setSecurityContext(new SecurityContext("803517ad-3bbc-4169-b085-60053a8f6dbf"));

        // Create a visual representation for the signature
        PadesVisualRepresentation visualRepresentation = new PadesVisualRepresentation();

        // The tags {{name}} and {{br_cpf_formatted}} will be substituted according to the user's certificate
        //
        //		name             : full name of the signer
        //		br_cpf_formatted : if the certificate is ICP-Brasil, contains the signer's CPF
        //
        // For a full list of the supported tags, see: https://github.com/LacunaSoftware/RestPkiSamples/blob/master/PadesTags.md
        PadesVisualText text = new PadesVisualText("Signed by {{name}} ({{br_cpf_formatted}})", true);
        // Optionally set the horizontal alignment of the text ('Left' or 'Right'), if not set the default is Left
        text.setHorizontalAlign(PadesTextHorizontalAlign.Left);
        // Add to visual representation
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

        // Set the document to be signed based on its ID (passed to us from the page)
        signatureStarter.setPdfToSign(Util.getBatchDocContent(id));

        /*
            Optionally, add marks to the PDF before signing. These differ from the signature visual representation in
            that they are actually changes done to the document prior to signing, not binded to any signature.
            Therefore, any number of marks can be added, for instance one per page, whereas there can only be one visual
            representation per signature. However, since the marks are in reality changes to the PDF, they can only be
            added to documents which have no previous signatures, otherwise such signatures would be made invalid by the
            changes to the document (see property PadesSignatureStarter.bypassMarksIfSigned). This problem does not
            occurr with signature visual representations.

            We have encapsulated this code in a method to include several possibilities depending on the argument
            passed. Experiment changing the argument to see different examples of PDF marks (valid values are 1-4). Once
            you decide which is best for your case, you can place the code directly here.
        */
        //signatureStarter.addPdfMark(PadesVisualElements.getPdfMark(1));


        // Call the startWithWebPki() method, which initiates the signature. This yields a SignatureStartWithWebPkiResult
        // object containing the signer certificate and the token, a 43-character case-sensitive URL-safe string, which
        // identifies this signature process. We'll use this value to call the signWithRestPki() method on the Web PKI
        // component (see file static/js/signature-form.js) and also to complete the signature after the form
        // is submitted (see method complete() below). This should not be mistaken with the API access token.
        SignatureStartWithWebPkiResult result = signatureStarter.startWithWebPki();

        // Return a JSON with the token obtained from REST PKI (the page will use jQuery to decode this value)
        return "\"" + result.getToken() + "\"";
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

        // Instantiate the PadesSignatureFinisher2 class, responsible for completing the signature process. For more
        // information, see:
        PadesSignatureFinisher2 signatureFinisher = new PadesSignatureFinisher2(Util.getRestPkiClient());

        // Set the token for this signature (rendered in a hidden input field, see file templates/pades-signature.html).
        signatureFinisher.setToken(token);

        // Call the finish() method, which finalizes the signature process and returns a SignatureResult object.
        SignatureResult signatureResult = signatureFinisher.finish();

        // At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
        // store the PDF on a temporary folder and return to the page an identifier that can be used to download it.

        // The SignatureResult object has various methods for writing the signature file to a stream (writeToFile()),
        // local file (writeToFile()), get its contents (getContent()). Avoid these methods to prevent memory allocation
        // issues with large files.
        String filename = UUID.randomUUID() + ".pdf";
        signatureResult.writeToFile(new File(Application.getTempFolderPath(), filename));

        return "\"" + filename + "\"";
    }
}

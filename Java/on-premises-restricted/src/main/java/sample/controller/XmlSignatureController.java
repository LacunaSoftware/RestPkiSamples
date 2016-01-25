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
import java.nio.file.Files;
import java.util.UUID;

@Controller
public class XmlSignatureController {

    /*
	 * This action renders the page for the first step of the signature process, on which the user will choose the
	 * certificate to be used to sign the file and we'll programmatically retrieve the certificate's encoding using
	 * the Web PKI component and send back to the server.
	 */
    @RequestMapping(value = "/xml-full-signature", method = {RequestMethod.GET})
    public String getFull(
            Model model
    ) throws IOException, RestException {
        // Render the page for the first step of the signature process, on which the user chooses the certificate and
        // its encoding is read (templates/xml-full-signature-step1.html)
        return "xml-full-signature-step1";
    }

    /*
     * This action receives the encoding of the certificate chosen by the user, uses it to initiate a XML signature
     * using REST PKI and renders the page for the final step of the signature process.
    */
    @RequestMapping(value = "/xml-full-signature", method = {RequestMethod.POST})
    public String postFull(
            @RequestParam(value = "selectedCertThumb", required = true) String selectedCertThumb,
            @RequestParam(value = "certificate", required = true) String certificate,
            Model model,
            HttpServletResponse response
    ) throws IOException, RestException {

        // Instantiate the FullXmlSignatureStarter class, responsible for receiving the signature elements and start the
        // signature process. For more information, see:
        // https://pki.rest/Content/docs/java-client/index.html?com/lacunasoftware/restpki/FullXmlSignatureStarter.html
        FullXmlSignatureStarter signatureStarter = new FullXmlSignatureStarter(Util.getRestPkiClient());

        // Set the XML to be signed, a sample XML Document
        signatureStarter.setXml(Util.getSampleXml());

        // Set the location on which to insert the signature node. If the location is not specified, the signature will appended
        // to the root element (which is most usual with enveloped signatures).
        XmlNamespaceManager nsm = new XmlNamespaceManager();
        nsm.addNamespace("ls", "http://www.lacunasoftware.com/sample");
        signatureStarter.setSignatureElementLocation("//ls:signaturePlaceholder", nsm, XmlInsertionOptions.AppendChild);

        // Set the certificate's encoding in base64 encoding (which is what the Web PKI component yields)
        signatureStarter.setSignerCertificate(certificate);

        // Set the signature policy
        signatureStarter.setSignaturePolicy(SignaturePolicy.XadesBasic);

        // Set a SecurityContext to be used to determine trust in the certificate chain
        signatureStarter.setSecurityContext(SecurityContext.pkiBrazil);
        // Note: By changing the SecurityContext above you can accept only certificates from a certain PKI,
        // for instance, ICP-Brasil (SecurityContext.pkiBrazil).

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
            String retryUrl = "/xml-full-signature";
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
        // (templates/xml-full-signature-step2.html)
        model.addAttribute("selectedCertThumb", selectedCertThumb);
        model.addAttribute("token", signatureInstructions.getToken());
        model.addAttribute("toSignHash", signatureInstructions.getToSignHash());
        model.addAttribute("digestAlg", signatureInstructions.getDigestAlgorithmOid());
        return "xml-full-signature-step2";
    }


    /*
	 * This action renders the page for the first step of the signature process, on which the user will choose the
	 * certificate to be used to sign the file and we'll programmatically retrieve the certificate's encoding using
	 * the Web PKI component and send back to the server.
	 */
    @RequestMapping(value = "/xml-element-signature", method = {RequestMethod.GET})
    public String getElement(
            Model model
    ) throws IOException, RestException {
        // Render the page for the first step of the signature process, on which the user chooses the certificate and
        // its encoding is read (templates/xml-full-signature-step1.html)
        return "xml-element-signature-step1";
    }

    /*
     * This action receives the encoding of the certificate chosen by the user, uses it to initiate a XML signature
     * using REST PKI and renders the page for the final step of the signature process.
    */
    @RequestMapping(value = "/xml-element-signature", method = {RequestMethod.POST})
    public String postElement(
            @RequestParam(value = "selectedCertThumb", required = true) String selectedCertThumb,
            @RequestParam(value = "certificate", required = true) String certificate,
            Model model,
            HttpServletResponse response
    ) throws IOException, RestException {

        // Instantiate the XmlElementSignatureStarter class, responsible for receiving the signature elements and start the
        // signature process. For more information, see:
        // https://pki.rest/Content/docs/java-client/index.html?com/lacunasoftware/restpki/XmlElementSignatureStarter.html
        XmlElementSignatureStarter signatureStarter = new XmlElementSignatureStarter(Util.getRestPkiClient());

        // Set the XML to be signed, a sample XML Document
        signatureStarter.setXml(Util.getSampleNFe());

        // Set the certificate's encoding in base64 encoding (which is what the Web PKI component yields)
        signatureStarter.setSignerCertificate(certificate);

        // Set the ID of the element to be signed
        signatureStarter.setElementToSIgnId("NFe35141214314050000662550010001084271182362300");

        // Set the signature policy
        signatureStarter.setSignaturePolicy(SignaturePolicy.NFePadraoNacional);
        // Optionally, set a SecurityContext to be used to determine trust in the certificate chain. Since we're using the
        // SignaturePolicy.NFePadraoNacional policy, the security context will default to PKI Brazil (ICP-Brasil)
        // signatureStarter.setSecurityContext(SecurityContext.pkiBrazil);
        // Note: By changing the SecurityContext above you can accept only certificates from a certain PKI,
        // for instance, ICP-Brasil (SecurityContext.pkiBrazil).

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
            String retryUrl = "/xml-element-signature";
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
        // (templates/xml-full-signature-step2.html)
        model.addAttribute("selectedCertThumb", selectedCertThumb);
        model.addAttribute("token", signatureInstructions.getToken());
        model.addAttribute("toSignHash", signatureInstructions.getToSignHash());
        model.addAttribute("digestAlg", signatureInstructions.getDigestAlgorithmOid());
        return "xml-element-signature-step2";
    }


    /*
     * This action receives the form submission from the page for the final step of the signature process. We'll call
     * REST PKI to complete the signature.
     */
    @RequestMapping(value = "/xml-signature-complete", method = {RequestMethod.POST})
    public String complete(
            @RequestParam(value = "token", required = true) String token,
            @RequestParam(value = "signature", required = true) String signature,
            Model model
    ) throws IOException, RestException {

        // Instantiate the FullXmlSignatureFinisher class, responsible for completing the signature process. For more
        // information, see:
        // https://pki.rest/Content/docs/java-client/index.html?com/lacunasoftware/restpki/XmlSignatureFinisher.html
        XmlSignatureFinisher signatureFinisher = new XmlSignatureFinisher(Util.getRestPkiClient());

        // Set the token for this signature (rendered in a hidden input field, see file templates/xml-full-signature.html)
        signatureFinisher.setToken(token);

        // Set the result of the signature operation
        signatureFinisher.setSignature(signature);

        // Call the finish() method, which finalizes the signature process and returns the signed XML's bytes
        byte[] signedXml;
        try {
            signedXml = signatureFinisher.finish();
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

        // At this point, you'd typically store the signed XML on your database. For demonstration purposes, we'll
        // store the XML on a temporary folder and return to the page an identifier that can be used to download it.

        String filename = UUID.randomUUID() + ".xml";
        Files.write(Application.getTempFolderPath().resolve(filename), signedXml);
        model.addAttribute("signerCert", signerCert);
        model.addAttribute("filename", filename);
        return "xml-signature-info";
    }
}

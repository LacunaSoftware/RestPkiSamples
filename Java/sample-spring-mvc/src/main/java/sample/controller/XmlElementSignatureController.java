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
public class XmlElementSignatureController {

    /*
	 * This action initiates a XML element signature using REST PKI and renders the signature page.
	 */
    @RequestMapping(value = "/xml-element-signature", method = {RequestMethod.GET})
    public String get(
            Model model,
            HttpServletResponse response
    ) throws IOException, RestException {

        // Instantiate the XmlElementSignatureStarter class, responsible for receiving the signature elements and start the
        // signature process. For more information, see:
        // https://pki.rest/Content/docs/java-client/index.html?com/lacunasoftware/restpki/XmlElementSignatureStarter.html
        XmlElementSignatureStarter signatureStarter = new XmlElementSignatureStarter(Util.getRestPkiClient());

        // Set the XML to be signed, a sample XML Document
        signatureStarter.setXml(Util.getSampleNFe());

        // Set the ID of the element to be signed
        signatureStarter.setElementToSIgnId("NFe35141214314050000662550010001084271182362300");

        // Set the signature policy
        signatureStarter.setSignaturePolicy(SignaturePolicy.NFePadraoNacional);
        // Optionally, set a SecurityContext to be used to determine trust in the certificate chain. Since we're using the
        // SignaturePolicy.NFePadraoNacional policy, the security context will default to PKI Brazil (ICP-Brasil)
        // signatureStarter.setSecurityContext(SecurityContext.pkiBrazil);
        // Note: By changing the SecurityContext above you can accept only certificates from a certain PKI,
        // for instance, ICP-Brasil (SecurityContext.pkiBrazil).

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

        // Render the signature page (templates/Xml-full-signature.html)
        model.addAttribute("token", token);
        return "xml-element-signature";
    }

    /*
     * This action receives the form submission from the signature page. We'll call REST PKI to complete the signature.
     */
    @RequestMapping(value = "/xml-element-signature", method = {RequestMethod.POST})
    public String post(
            @RequestParam(value = "token", required = true) String token,
            Model model
    ) throws IOException, RestException {

        // Instantiate the XmlSignatureFinisher class, responsible for completing the signature process. For more
        // information, see:
        // https://pki.rest/Content/docs/java-client/index.html?com/lacunasoftware/restpki/XmlSignatureFinisher.html
        XmlSignatureFinisher signatureFinisher = new XmlSignatureFinisher(Util.getRestPkiClient());

        // Set the token for this signature (rendered in a hidden input field, see file templates/pades-signature.html)
        signatureFinisher.setToken(token);

        // Call the finish() method, which finalizes the signature process and returns the signed XML's bytes
        byte[] signedXml = signatureFinisher.finish();

        // Get information about the certificate used by the user to sign the file. This method must only be called after
        // calling the finish() method.
        PKCertificate signerCert = signatureFinisher.getCertificateInfo();

        // At this point, you'd typically store the signed PDF on your database. For demonstration purposes, we'll
        // store the XML on a temporary folder and return to the page an identifier that can be used to download it.

        String filename = UUID.randomUUID() + ".xml";
        Files.write(Application.getTempFolderPath().resolve(filename), signedXml);
        model.addAttribute("signerCert", signerCert);
        model.addAttribute("filename", filename);
        return "xml-signature-info";
    }
}

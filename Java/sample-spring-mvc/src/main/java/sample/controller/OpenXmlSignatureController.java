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
import java.util.List;

@Controller
public class OpenXmlSignatureController {

    /**
     * This action submits a Xml file to Rest PKI for inspection of its signatures.
     */
    @RequestMapping(value = "/open-xml-signature", method = {RequestMethod.GET})
    public String get(
            @RequestParam(value = "userfile", required = false) String userfile,
            Model model,
            HttpServletResponse response
    ) throws IOException, RestException {

        // Get an instance of the PadesSignatureExplorer class, used to open/validate XML signatures
        XmlSignatureExplorer sigExplorer = new XmlSignatureExplorer(Util.getRestPkiClient());

        // Set the XML file
        sigExplorer.setSignatureFile(Application.getTempFolderPath().resolve(userfile));

        // Specify that we want to validate the signatures in the file, not only inspect them
        sigExplorer.setValidate(true);

        // Parameters for the signature validation. We have encapsulated this code in a method to include several
        // possibilities depending on the argument passed. Experiment changing the argument to see different validation
        // configurations (valid values are 1-2). Once you decide which is best for your case, you can place the code
        // directly here.
        setValidationParameters(sigExplorer, 2);
        // try changing this number ---------^ for different validation parameters

        // Call the open() method, which returns a list of signatures found in the XML file.
        List<XmlSignature> signatures = sigExplorer.open();

        // Render the information (see file resources/templates/open-xml-signature.html for more information on the
        // information returned)
        model.addAttribute("signatures", signatures);

        return "open-xml-signature";


    }

    private static void setValidationParameters(XmlSignatureExplorer sigExplorer, int caseNumber) {

        switch (caseNumber) {

            /*
             *  Example #1: accept any valid XmlDSig signature as long as the signer has an ICP-Brasil certificate
             *
             *  These parameters will only accept signatures made with ICP-Brasil certificates that comply with the
             *  minimal security features defined in the XmlDSig standard. The signatures need not, however, follow
             *  the extra requirements defined in the ICP-Brasil signature policy documentation (DOC-ICP-15.03).
             */
            case 1:
                // By omitting the accepted policies catalog and defining a default policy, we're telling Rest PKI to
                // validate all signatures in the file with the default policy -- even signatures with an explicit
                // signature policy.
                sigExplorer.setAcceptableExplicitPolicies(null);
                sigExplorer.setDefaultSignaturePolicy(SignaturePolicy.XmlDSigBasic);
                // The XmlDSigBasic policy requires us to choose a security context
                sigExplorer.setSecurityContext(SecurityContext.pkiBrazil);
                break;

            /*
             * Example #2: accept any valid XmlDSig signature as long as the signer is trusted by Windows.
             *
             * Same case as example #1, but using the WindowsSever trust arbitrator.
             */
            case 2:
                sigExplorer.setAcceptableExplicitPolicies(null);
                sigExplorer.setDefaultSignaturePolicy(SignaturePolicy.XmlDSigBasic);
                sigExplorer.setSecurityContext(SecurityContext.windowsServer);
                break;
        }
    }
}

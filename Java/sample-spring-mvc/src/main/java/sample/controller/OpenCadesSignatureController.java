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

@Controller
public class OpenCadesSignatureController {

    /**
	 * This action submits a CAdES signature file to Rest PKI for inspection.
     */
    @RequestMapping(value = "/open-cades-signature", method = {RequestMethod.GET})
    public String get(
            @RequestParam(value = "userfile", required = false) String userfile,
            Model model,
            HttpServletResponse response
    ) throws IOException, RestException {

        // Get an instance of the CadesSignatureExplorer2 class, used to open/validate CAdES signatures.
        CadesSignatureExplorer2 sigExplorer = new CadesSignatureExplorer2(Util.getRestPkiClient());

        // Set the CAdES signature file
        sigExplorer.setSignatureFile(Application.getTempFolderPath().resolve(userfile));

        // Specify that we want to validate the signatures in the file, not only inspect them
        sigExplorer.setValidate(true);

        // Parameters for the signature validation. We have encapsulated this code in a method to include several
        // possibilities depending on the argument passed. Experiment changing the argument to see different validation
        // configurations (valid values are 1-5). Once you decide which is best for your case, you can place the code
        // directly here.
        setValidationParameters(sigExplorer, 1);
        // try changing this number ---------^ for different validation parameters

        // Call the open() method, which returns the signature file's information
        CadesSignature signature = sigExplorer.open();

        // Render the information (see file resources/templates/open-cades-signature.html for more information on the
        // information returned)
        model.addAttribute("signature", signature);

        return "open-cades-signature";
    }

    private static void setValidationParameters(CadesSignatureExplorer2 sigExplorer, int caseNumber) {

        switch (caseNumber) {

            /**
             * Example #1: accept only 100%-compliant ICP-Brasil signatures.
             */
            case 1:
                // By specifying a catalog of acceptable policies and omitting the default signature policy, we're
                // telling Rest PKI that only the policies in the catalog should be accepted.
                sigExplorer.setAcceptableExplicitPolicies(SignaturePolicyCatalog.getPkiBrazilCades());
                break;

            /**
             * Example #2: accept any CAdES signature as long as the signer has an ICP-Brasil certificate.
             *
             * These parameters will only accept signatures made with ICP-Brasil certificates that comply with the
             * minimal security features defined in the CAdES standard (ETSI TS 101 733). The signatures need not,
             * however, follow the extra requirements defined in the ICP-Brasil signature policy
             * documentation (DOC-ICP-15.03).
             *
             * These parameters are less restrictive than the parameters from example #1.
             */
            case 2:
                // By omitting the accepted policies catalog and defining a default policy, we're telling Rest PKI to
                // validate all signatures in the file with the default policy -- even signatures with an explicit
                // signature policy.
                sigExplorer.setDefaultSignaturePolicy(SignaturePolicy.CadesBes);
                // The CadesBes policy requires us to choose a security context.
                sigExplorer.setSecurityContext(SecurityContext.pkiBrazil);
                break;

            /**
             * Example #3: accept any CAdES signature as long as the signer is trusted by Windows.
             *
             * Same case as example #2, but using the WindowsServer trust arbitrator.
             */
            case 3:
                sigExplorer.setDefaultSignaturePolicy(SignaturePolicy.CadesBes);
                sigExplorer.setSecurityContext(SecurityContext.windowsServer);
                break;

            /**
             * Example #4: accept only 100%-compliant ICP-Brasil signatures that provide signer certificate protection.
             *
             * "Signer certificate protection" means that a signature keeps its validity even after the signer
             * certificate is revoked or expires. On ICP-Brasil, this translates to policies AD-RT and up (not AD-RB).
             */
            case 4:
                sigExplorer.setAcceptableExplicitPolicies(SignaturePolicyCatalog.getPkiBrazilCadesWithSignerCertificateProtection());
                break;

            /**
             * Example #5: accept only 100%-compliant ICP-Brasil signatures that provide CA certificate
             * protection (besides signer certificate protection).
             *
             * "CA certificate protection" means that a signature keeps its validity even after either the signer
             * certificate or its Certification Authority (CA) certificate expires or is revoked. On ICP-Brasil, this
             * translates to policies AD-RC/AD-RV and up (not AD-RB nor AD-RT).
             */
            case 5:
                sigExplorer.setAcceptableExplicitPolicies(SignaturePolicyCatalog.getPkiBrazilCadesWithCACertificateProtection());
                break;

        }
    }
}

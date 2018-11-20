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
public class OpenPadesSignatureController {

	/**
	 * This action submits a PDF file to Rest PKI for inspection of its signatures.
	 */
	@RequestMapping(value = "/open-pades-signature", method = {RequestMethod.GET})
	public String get(
		@RequestParam(value = "userfile", required = false) String userfile,
		Model model
	) throws IOException, RestException {

		// Get an instance the PadesSignatureExplorer2 class, used to open/validate PDF signatures.
		PadesSignatureExplorer2 sigExplorer = new PadesSignatureExplorer2(Util.getRestPkiClient());

		// Specify that we want to validate the signatures in the file, not only inspect them.
		sigExplorer.setValidate(true);

		// Set the PDF file.
		sigExplorer.setSignatureFile(Application.getTempFolderPath().resolve(userfile));

		// Specify the parameters for the signature validation:
		// Accept any PAdES signature as long as the signer has an ICP-Brasil certificate.
		sigExplorer.setDefaultSignaturePolicy(SignaturePolicy.PadesBasic);
		// Specify the security context to be used to determine trust in the certificate chain. We
		// have encapsulated the security context on Util.java.
		sigExplorer.setSecurityContext(Util.getSecurityContextId());

		// Call the open() method, which returns the signature file's information.
		PadesSignature signature = sigExplorer.open();

		// Render the information (see file resources/templates/open-pades-signature.html for more
		// information on the information returned).
		model.addAttribute("signature", signature);
		return "open-pades-signature";
	}
}

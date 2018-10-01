package sample.controller;

import com.lacunasoftware.restpki.*;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.core.io.ClassPathResource;
import org.springframework.core.io.Resource;
import sample.Application;
import sample.util.Util;

import javax.servlet.http.HttpServletResponse;
import java.io.File;
import java.io.IOException;
import java.io.InputStream;

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

		// Get an instance of the CadesSignatureExplorer2 class, used to open/validate CAdES
		// signatures.
		CadesSignatureExplorer2 sigExplorer = new CadesSignatureExplorer2(Util.getRestPkiClient());

		// Set the CAdES signature file
		sigExplorer.setSignatureFile(new File(Application.getTempFolderPath(), userfile));

		// Specify that we want to validate the signatures in the file, not only inspect them
		sigExplorer.setValidate(true);

		// Specify the parameters for the signature validation:
		// Accept any CAdES signature as long as the signer certificate is compatible with the
		// provided security context.
		sigExplorer.setDefaultSignaturePolicy(SignaturePolicy.CadesBes);
		sigExplorer.setSecurityContext(Util.getSecurityContextId());
		// We have encapsulated the security context choice on Util.java.

		// Alternatively, you may require full compliance with ICP-Brasil by doing:
		//igExplorer.setAcceptableExplicitPolicies(SignaturePolicyCatalog.getPkiBrazilCades());
		//sigExplorer.setDefaultSignaturePolicy(null);
		//sigExplorer.setSecurityContext(null);

		// Call the open() method, which returns the signature file's information
		CadesSignature signature = sigExplorer.open();

		// Render the information (see file resources/templates/open-cades-signature.html for more
		// information on the information returned).
		model.addAttribute("signature", signature);

		return "open-cades-signature";
	}
}

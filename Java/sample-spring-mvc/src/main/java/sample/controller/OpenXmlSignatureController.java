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
	 * This action submits a XML file to Rest PKI for inspection of its signatures.
	 */
	@RequestMapping(value = "/open-xml-signature", method = {RequestMethod.GET})
	public String get(
			@RequestParam(value = "userfile", required = false) String userfile,
			Model model,
			HttpServletResponse response
	) throws IOException, RestException {

		// Get an instance of the XmlSignatureExplorer class, used to open/validate XML signatures.
		XmlSignatureExplorer sigExplorer = new XmlSignatureExplorer(Util.getRestPkiClient());

		// Specify that we want to validate the signatures in the file, not only inspect them.
		sigExplorer.setValidate(true);

		// Specify the parameters for the signature validation:
		// Accept any valid XmlDSig Signature as long as the signer has an ICP-Brasil certificate.
		sigExplorer.setDefaultSignaturePolicy(SignaturePolicy.XmlDSigBasic);

		// Specify the security context to be used to determine trust in the certificate chain. We
		// have encapsulated the security context choice in Util.java.
		sigExplorer.setSecurityContext(Util.getSecurityContextId());

		// Set the XML file.
		sigExplorer.setSignatureFile(Application.getTempFolderPath().resolve(userfile));

		// Call the open() method, which returns a list of signatures found in the XML file.
		List<XmlSignature> signatures = sigExplorer.open();

		// Render the information (see file resources/templates/open-xml-signature.html for more
		// information on the information returned).
		model.addAttribute("signatures", signatures);

		return "open-xml-signature";
	}
}

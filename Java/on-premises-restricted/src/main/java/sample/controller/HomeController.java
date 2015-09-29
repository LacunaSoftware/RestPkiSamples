package sample.controller;


import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.RequestMapping;
import sample.util.Util;

@Controller
public class HomeController {

	@RequestMapping("/")
	public String index() {
		return "index";
	}

	@RequestMapping("/Home/Authentication")
	public String authentication() {
		// Checks that the access token was set (this can be removed on production code)
		Util.checkAccessToken();
		// Render authentication view
		return "authentication";
	}

	@RequestMapping("/Home/PadesSignature")
	public String padesSignature() {
		// Checks that the access token was set (this can be removed on production code)
		Util.checkAccessToken();
		// Render PAdES signature view
		return "pades-signature";
	}
}


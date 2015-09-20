package sample.controller;


import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.RequestMapping;

@Controller
public class HomeController {
	
    @RequestMapping("/")
    public String index() {
        return "index";
    }
    
    @RequestMapping("/Home/Authentication")
    public String authentication() {
    	return "authentication";
    }

    @RequestMapping("/Home/PadesSignature")
    public String padesSignature() {
        return "pades-signature";
    }
}


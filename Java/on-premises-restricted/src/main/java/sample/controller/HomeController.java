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

}


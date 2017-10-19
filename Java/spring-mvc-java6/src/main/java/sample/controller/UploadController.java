package sample.controller;

import com.lacunasoftware.restpki.RestException;
import com.lacunasoftware.restpki.Storage;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.multipart.MultipartFile;
import sample.Application;

import java.io.File;
import java.io.IOException;
import java.util.UUID;

@Controller
public class UploadController {

	@RequestMapping(value = "/upload", method = {RequestMethod.GET})
	public String get(@RequestParam(value = "goto", required = true) String goTo) {
		return "upload";
	}

	@RequestMapping(value = "/upload", method = {RequestMethod.POST})
	public String post(@RequestParam(value = "goto", required = true) String goTo, @RequestParam("userfile") MultipartFile userfile) throws IOException {
		byte[] fileContent = userfile.getBytes();
		String originalFilename = userfile.getOriginalFilename();
		int i = originalFilename.lastIndexOf('.');
		String fileExtension;
		if (i >= 0) {
			fileExtension = originalFilename.substring(i);
		} else {
			fileExtension = "";
		}
		String filename = UUID.randomUUID() + fileExtension;
		Storage.writeFile(new File(Application.getTempFolderPath(), filename), fileContent);
		return "redirect:/" + goTo + "?userfile=" + filename;
	}

}

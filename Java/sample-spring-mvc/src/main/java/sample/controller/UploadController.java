package sample.controller;

import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.multipart.MultipartFile;
import sample.Application;

import java.io.IOException;
import java.nio.file.Files;
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
		Files.write(Application.getTempFolderPath().resolve(filename), fileContent);
		return "redirect:/" + goTo + "?userfile=" + filename;
	}

}

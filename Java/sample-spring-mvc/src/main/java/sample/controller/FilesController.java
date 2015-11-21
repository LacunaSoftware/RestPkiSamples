package sample.controller;

import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import sample.Application;

import javax.servlet.http.HttpServletResponse;
import java.io.IOException;
import java.io.OutputStream;
import java.nio.file.Files;

@Controller
public class FilesController {

	@RequestMapping("/files/{filename:.+}")
	public void get(HttpServletResponse httpResponse, @PathVariable("filename") String filename) throws IOException {
		byte[] content = Files.readAllBytes(Application.getTempFolderPath().resolve(filename));
		httpResponse.setHeader("Content-Disposition", String.format("attachment; filename=%s", filename));
		OutputStream outStream = httpResponse.getOutputStream();
		org.apache.commons.io.IOUtils.write(content, outStream);
		outStream.close();
	}

}

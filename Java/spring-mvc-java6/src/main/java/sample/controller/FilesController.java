package sample.controller;

import com.lacunasoftware.restpki.Storage;
import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import sample.Application;

import java.io.File;
import java.io.IOException;
import java.io.OutputStream;
import javax.servlet.http.HttpServletResponse;

@Controller
public class FilesController {

	@RequestMapping("/files/{filename:.+}")
	public void get(HttpServletResponse httpResponse, @PathVariable("filename") String filename) throws IOException {
		byte[] content = Storage.readFile(new File(Application.getTempFolderPath(), filename));
		httpResponse.setHeader("Content-Disposition", String.format("attachment; filename=%s", filename));
		OutputStream outStream = httpResponse.getOutputStream();
		org.apache.commons.io.IOUtils.write(content, outStream);
		outStream.close();
	}

}

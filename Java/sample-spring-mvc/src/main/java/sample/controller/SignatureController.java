package sample.controller;

import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import sample.util.DatabaseMock;
import sample.util.Util;

import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;
import java.io.IOException;
import java.io.OutputStream;

@Controller
public class SignatureController {

    @RequestMapping("/Signature/SampleDocument")
    public void getSampleDocument(HttpServletResponse httpResponse) throws IOException {
        byte[] pdfContent = Util.getSampleDocContent();
        httpResponse.setContentType("application/pdf");
        httpResponse.setHeader("Content-Disposition", "attachment; filename=SampleDocument.pdf");
        OutputStream outStream = httpResponse.getOutputStream();
        org.apache.commons.io.IOUtils.write(pdfContent, outStream);
        outStream.close();
    }

    @RequestMapping("/Signature/Download/{id}")
    public void download(HttpServletRequest httpRequest, HttpServletResponse httpResponse, @PathVariable("id") String id) throws IOException {
        DatabaseMock dbMock = new DatabaseMock(httpRequest.getSession());
        byte[] pdfContent = dbMock.getSignedPdf(id);
        httpResponse.setContentType("application/pdf");
        httpResponse.setHeader("Content-Disposition", String.format("attachment; filename=%s.pdf", id));
        OutputStream outStream = httpResponse.getOutputStream();
        org.apache.commons.io.IOUtils.write(pdfContent, outStream);
        outStream.close();
    }

}

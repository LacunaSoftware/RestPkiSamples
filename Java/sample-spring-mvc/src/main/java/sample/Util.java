package sample;

import com.lacunasoftware.restpki.SecurityContext;
import org.springframework.core.io.ClassPathResource;
import org.springframework.core.io.Resource;

import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.InputStream;

public class Util {

    public static String getRestPkiEndpoint() {
        return "https://restpki.lacunasoftware.com/";
    }

    public static String getAuthToken() {
        return "PLACE YOUR ACCESS TOKEN HERE";
    }

    public static SecurityContext getSecurityContext() {
        return SecurityContext.pkiBrazil;
    }

    public static byte[] getSampleDocContent() throws IOException {
        Resource resource = new ClassPathResource("/static/SampleDocument.pdf");
        InputStream fileStream = resource.getInputStream();
        ByteArrayOutputStream buffer = new ByteArrayOutputStream();
        org.apache.commons.io.IOUtils.copy(fileStream, buffer);
        fileStream.close();
        buffer.flush();
        return buffer.toByteArray();
    }

    public static byte[] getPdfStampContent() throws IOException {
        Resource resource = new ClassPathResource("/static/PdfStamp.png");
        InputStream fileStream = resource.getInputStream();
        ByteArrayOutputStream buffer = new ByteArrayOutputStream();
        org.apache.commons.io.IOUtils.copy(fileStream, buffer);
        fileStream.close();
        buffer.flush();
        return buffer.toByteArray();
    }
}

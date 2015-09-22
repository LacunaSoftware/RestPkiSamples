package sample.util;

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
		// -------------------------------------------------------------------------------------------
        return "PASTE YOUR ACCESS TOKEN HERE";
		// -------------------------------------------------------------------------------------------
    }

    public static SecurityContext getSecurityContext() {
        /*
         * By changing the argument below, you can accept signatures only signed with certificates
         * from a certain PKI, for instance, ICP-Brasil (SecurityContext.pkiBrazil) or Italy's PKI (SecurityContext.pkiItaly).
         */
        return SecurityContext.pkiBrazil;
        /*
         * You can also define a custom security context on the REST PKI website accepting whatever
         * root certification authorities you desire and then reference that context by its ID.
         */
        //return new SecurityContext("id-of-your-custom-security-context");
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

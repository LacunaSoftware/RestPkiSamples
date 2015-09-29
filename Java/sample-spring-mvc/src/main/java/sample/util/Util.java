package sample.util;

import com.lacunasoftware.restpki.RestPkiClient;
import com.lacunasoftware.restpki.SecurityContext;
import org.springframework.core.io.ClassPathResource;
import org.springframework.core.io.Resource;

import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.InputStream;

public class Util {

    // ----------------------------------------------------------------------------------------------------------------
    // PASTE YOUR API ACCESS TOKEN BELOW
    // ----------------------------------------------------------------------------------------------------------------
    //
    private static final String restPkiAccessToken = "";
    //                                               ^^----- API access token goes here
    // ----------------------------------------------------------------------------------------------------------------

    public static RestPkiClient getRestPkiClient() {
        checkAccessToken();
        return new RestPkiClient("https://pki.rest/", restPkiAccessToken);
    }

    public static void checkAccessToken() {
        if (restPkiAccessToken == null || restPkiAccessToken.equals("")) {
            throw new RuntimeException("The API access token was not set! Hint: to run this sample you must generate an API access token on the REST PKI website and paste it on the file src/main/java/sample/util/Util.java");
        }
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

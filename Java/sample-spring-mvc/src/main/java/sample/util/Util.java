package sample.util;

import com.lacunasoftware.restpki.SecurityContext;
import org.springframework.core.io.ClassPathResource;
import org.springframework.core.io.Resource;

import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.InputStream;

public class Util {

    public static String getRestPkiEndpoint() {
        //return "https://restpki.lacunasoftware.com/";
        return "https://restpkibeta.azurewebsites.net/";
    }

    public static String getAuthToken() {
        return "avsuNZU9jptSbC2-hco6899Gq12roN_88uEZF-M6pPvS6uf03DqIFoFw2qplFatvKKq94DYUho9AWCzYSIND5w3uDRpzhHJcrIPwluMI5AB_OsWFl9iZcyxga8b6qYftgzuxce6C7V2Dy4YK8zaeOQSHDKilpCkiJLmdaYF5mXlect3VB4nqqOH1QjK0Wvdo45K_OlZpsyTXXxhJHr-3AQnc17F98K3m8VLKDYmlgvHsc0zEko5k2ake4QdfvUrgrM2TQ6lhDoPy0EQ7zGLrDVlUEV50_wEe_fxJvh4QVb30gPuOlNP8IrFwhtDqCRoYdFGRh8VoR2juU-zBJsWMgoctjSjENsqWRcKmoKhVXh9xfkeSbgjYuGPBWr3jEmeTADq9Ug1wZ9zlpLOSFlUVMLCg7oiPwaJ9b23fXlKxXCUIbqcLJNkbk94UllzrZEmeHcE1D4PfG-CSXPBtDtYpuxFK236FtY8P7sEZBJ-o2D0Q6nnuxj5KjZDspl7EqcHb4nMFog";
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

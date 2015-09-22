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
        //return "https://restpkibeta.azurewebsites.net/";
    }

    public static String getAuthToken() {
        return "lie0i89rmua-cXwA7KKS1P8XBDmZSPPkr93b2arD-PlO4nPTsruYGGJ395vsL0WDZQLqdYHHcmi5QCqI2C-dfsHSO5W-yCs6H-ui5TDDE445qLwg7K4bMq5-rY8B5c0_yzFmEgEEdbbT41sj_ryPqpcbhygWvz86J1-bUZrV4FbRt9Ew64rFdQnhSuMUTHavSUGrQjjJL57L5FOzmTo5SNTHqBtOAkP9zK0s1FxsIXZb_04z_u-snd25xE1PFOeF5nwWbipowe-w44jGUY9lZ9_OzHpTikMvdZA-r3c4WwIz4srwsaK4HphuNmPgaHKrnDdjfICV1uuvt7pwqRNiiWbw6uZL_8AA8jntgPjCvORnKL37hlD_g7nVtADHZlD8EuvBOFk1U9vdqEVU6D6yPOLxVt-4RLThePAS0i8vrO-eRzeCkPghKjD1KGVIBerwXpH9cbHfc7B0NgLq81KehCGhd3JhRmycsulha47W5Q6QSqkS2cn7Mm9A_4WWsuTikULQLQ";
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

package sample;

import com.lacunasoftware.restpki.SecurityContext;
import org.springframework.core.io.ClassPathResource;
import org.springframework.core.io.Resource;

import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.InputStream;

public class Util {

    public static String getRestPkiEndpoint() {
        //return "http://localhost:53358/";
        return "https://restpki.lacunasoftware.com/";
    }

    public static String getAuthToken() {
        return "QKnLTez_3noV5pQSCXOalIB1sAKveEdbsmV4JdWZrhNfgink9a0w4nOzbFstmubzIVdniqUGyq4FP0qk79Vrlasx1KxCYAZtEKWgIW95_DY4PnXEi21lDzE5hiX0AwLka6bCGmcls5sjJAc6G0r1AbL3M_dT8ns6u1vH7NRZwQhl8BE2Qs500iKbyDvB5ocdzluceqziiX0ls8rNAZsMVJwbkwMo5t2iZeR64C1HGXkDMMz5F75C_eOX5STkEwmNVYVbBTrZ5GQCxRIdsZFB9hQGYL97TSYao5h-maVISkLYnFrrp5K5yPh1jLf1uwUEUa6LqrFHunA0IUhJczoIto15Fy5xe5wgbT825Y8On-E9gMf_XOmmTv-ko4CeSJe6gPJGCICPzsp3Q-z5C6FJm77zYVLtX_e355uEblVfUTuvVm_70zJh_4O-r8KmrbZm_69afkhq04KHC_1RxuNhuWkk-lWZP4VjYRPmqKTPLSh9XKx9yzrVzH9PSju58JFNG35aHkmp4konfsaaLNoOLw";
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

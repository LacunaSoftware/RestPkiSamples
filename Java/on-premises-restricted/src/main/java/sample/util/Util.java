package sample.util;

import com.lacunasoftware.restpki.RestPkiClient;
import com.lacunasoftware.restpki.SecurityContext;
import com.lacunasoftware.restpki.ValidationResults;
import org.springframework.core.io.ClassPathResource;
import org.springframework.core.io.Resource;

import javax.servlet.http.HttpServletResponse;
import javax.servlet.http.HttpSession;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.net.Proxy;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.util.UUID;

public class Util {

	// ----------------------------------------------------------------------------------------------------------------
	// PASTE YOUR API ACCESS TOKEN BELOW
	private static final String restPkiAccessToken = "PASTE YOUR API ACCESS TOKEN HERE";
	//                                                ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
	// ----------------------------------------------------------------------------------------------------------------

	// ----------------------------------------------------------------------------------------------------------------
	// PASTE THE URL OF YOUR ON-PREMISES INSTANCE OF REST PKI BELOW
	private static final String restPkiUrl = "https://pki.rest/";
	//                                        ^^^^^^^^^^^^^^^^^
	// ----------------------------------------------------------------------------------------------------------------


	public static RestPkiClient getRestPkiClient() {
		
		// Throw exception if token is not set (this check is here just for the sake of newcomers, you can remove it)
		if (restPkiAccessToken == null || restPkiAccessToken.equals("") || restPkiAccessToken.contains(" API ")) {
			throw new RuntimeException("The API access token was not set! Hint: to run this sample you must generate an API access token on the REST PKI website and paste it on the file src/main/java/sample/util/Util.java");
		}
		
		Proxy proxy = null;

		// --------------------------------------------------------------------------------------------------------------
		// If you need to set a proxy for outgoing connections, uncomment the line below and set the appropriate values
		// --------------------------------------------------------------------------------------------------------------
//		proxy = new Proxy(Proxy.Type.HTTP, new InetSocketAddress("10.1.1.10", 80));

		// --------------------------------------------------------------------------------------------------------------
		// If your proxy requires authentication, uncomment the lines below and set the appropriate values
		// --------------------------------------------------------------------------------------------------------------
//		Authenticator.setDefault(
//			new Authenticator() {
//				public PasswordAuthentication getPasswordAuthentication() {
//					return new PasswordAuthentication("username", "password".toCharArray());
//				}
//			}
//		);

		return new RestPkiClient(restPkiUrl, restPkiAccessToken, proxy);
	}

	public static void setNoCacheHeaders(HttpServletResponse response) {
		response.setHeader("Expires", "-1");
		response.setHeader("Cache-Control", "no-cache, no-store, must-revalidate");
		response.setHeader("Pragma", "no-cache");
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

    public static byte[] getSampleXml() throws IOException {
        Resource resource = new ClassPathResource("/static/SampleDocument.xml");
        InputStream fileStream = resource.getInputStream();
        ByteArrayOutputStream buffer = new ByteArrayOutputStream();
        org.apache.commons.io.IOUtils.copy(fileStream, buffer);
        fileStream.close();
        buffer.flush();
        return buffer.toByteArray();
    }

    public static byte[] getSampleNFe() throws IOException {
        Resource resource = new ClassPathResource("/static/SampleNFe.xml");
        InputStream fileStream = resource.getInputStream();
        ByteArrayOutputStream buffer = new ByteArrayOutputStream();
        org.apache.commons.io.IOUtils.copy(fileStream, buffer);
        fileStream.close();
        buffer.flush();
        return buffer.toByteArray();
    }

	public static String getValidationResultsHtml(ValidationResults vr) {
		return vr.toString().replaceAll("\n", "<br>").replaceAll("\t", "&nbsp;&nbsp;&nbsp;&nbsp;");
	}
}

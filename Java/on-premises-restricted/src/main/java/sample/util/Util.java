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
	private static final String restPkiAccessToken = "PLACE YOUR API ACCESS TOKEN HERE";
	//                                                ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
	// ----------------------------------------------------------------------------------------------------------------

	public static RestPkiClient getRestPkiClient() {
		checkAccessToken();
		return new RestPkiClient("https://pki.rest/", restPkiAccessToken);
	}

	public static void checkAccessToken() {
		if (restPkiAccessToken.contains(" API ")) {
			throw new RuntimeException("The API access token was not set! Hint: to run this sample you must generate an API access token on the REST PKI website and paste it on the file src/main/java/sample/util/Util.java");
		}
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

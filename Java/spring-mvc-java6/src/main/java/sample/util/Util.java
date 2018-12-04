package sample.util;

import com.lacunasoftware.restpki.RestPkiClient;
import com.lacunasoftware.restpki.SecurityContext;
import org.springframework.core.io.ClassPathResource;
import org.springframework.core.io.Resource;
import sample.Application;

import javax.servlet.http.HttpServletResponse;
import javax.servlet.http.HttpSession;
import java.io.ByteArrayOutputStream;
import java.io.File;
import java.io.IOException;
import java.io.InputStream;
import java.net.Authenticator;
import java.net.InetSocketAddress;
import java.net.PasswordAuthentication;
import java.net.Proxy;
import java.util.Arrays;
import java.util.UUID;

public class Util {

	public static RestPkiClient getRestPkiClient() {

		String accessToken = Application.environment.getProperty("restpki.accessToken");

		// Throw exception if token is not set (this check is here just for the sake of newcomers,
		// you can remove it).
		if (accessToken == null || accessToken.equals("") || accessToken.contains(" API ")) {
			throw new RuntimeException("The API access token was not set! Hint: to run this sample you must generate an API access token on the REST PKI website and paste it on the file src/main/resources/application.properties");
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

		// ------------------------------------------------------------------------------------------
		// IMPORTANT NOTICE: in production code, you should use HTTPS to communicate with REST PKI,
		// otherwise your API access token, as well as the documents you sign, will be sent to
		// REST PKI unencrypted.
		// ------------------------------------------------------------------------------------------
		String endpoint = Application.environment.getProperty("restpki.endpoint");
		if (endpoint == null || endpoint.length() == 0) {
			endpoint = "http://pki.rest/";
			//endpoint = "https://pki.rest/"; // <--- USE THIS IN PRODUCTION!
		}

		return new RestPkiClient(endpoint, accessToken, proxy);
	}

	public static SecurityContext getSecurityContextId() {

		if (Arrays.asList(Application.environment.getActiveProfiles()).contains("development")) {

			/*
				Lacuna Test PKI (for development purposes only!)

				This security context trusts ICP-Brasil certificates as well as certificates on
				Lacuna Software's test PKI. Use it to accept the test certificates provided by Lacuna
				Software.

				THIS SHOULD NEVER BE USED ON A PRODUCTION ENVIRONMENT!
				For more information, see https://github.com/LacunaSoftware/RestPkiSamples/blob/master/TestCertificates.md
			 */
			return SecurityContext.lacunaTest;
		} else {

			// In production, accept only certificates from ICP-Brasil.
			return SecurityContext.pkiBrazil;
		}
	}

	public static void setNoCacheHeaders(HttpServletResponse response) {
		response.setHeader("Expires", "-1");
		response.setHeader("Cache-Control", "no-cache, no-store, must-revalidate");
		response.setHeader("Pragma", "no-cache");
	}

	public static File getSampleDocFile() throws IOException {
		return new ClassPathResource("/static/SampleDocument.pdf").getFile();
	}

	public static byte[] getSampleXmlContent() throws IOException {
		Resource resource = new ClassPathResource("/static/SampleDocument.xml");
		InputStream fileStream = resource.getInputStream();
		ByteArrayOutputStream buffer = new ByteArrayOutputStream();
		org.apache.commons.io.IOUtils.copy(fileStream, buffer);
		fileStream.close();
		buffer.flush();
		return buffer.toByteArray();
	}

	public static File getSampleXmlFile() throws IOException {
		return new ClassPathResource("/static/SampleDocument.xml").getFile();
	}

	public static File getSampleNFeFile() throws IOException {
		return new ClassPathResource("/static/SampleNFe.xml").getFile();
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

	public static File getBatchDocFile(int id) throws IOException {
		return new ClassPathResource("/static/" + String.format("%02d", id % 10) + ".pdf").getFile();
	}

	public static File createTempDirectory(String dirName) throws IOException {
		File temp = File.createTempFile(dirName, "");
		temp.delete();
		temp.mkdir();
		return temp;
	}

}

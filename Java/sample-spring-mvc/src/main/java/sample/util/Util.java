package sample.util;

import com.lacunasoftware.restpki.RestPkiClient;
import com.lacunasoftware.restpki.SecurityContext;
import org.apache.commons.io.IOUtils;
import org.springframework.core.io.ClassPathResource;
import org.springframework.core.io.Resource;
import sample.Application;

import javax.servlet.http.HttpServletResponse;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.net.Authenticator;
import java.net.InetSocketAddress;
import java.net.PasswordAuthentication;
import java.net.Proxy;
import java.nio.file.Path;
import java.security.*;
import java.security.cert.Certificate;
import java.util.*;

public class Util {

	public static RestPkiClient getRestPkiClient() {

		String accessToken = Application.environment.getProperty("restpki.accessToken");

		// Throw exception if token is not set (this check is here just for the sake of newcomers,
		// you can remove it).
		if (accessToken == null || accessToken.equals("") || accessToken.contains(" API ")) {
			throw new RuntimeException("The API access token was not set! Hint: to run this sample you must generate an API access token on the REST PKI website and paste it on the file src/main/resources/application.properties");
		}

		Proxy proxy = null;

		// ------------------------------------------------------------------------------------------
		// If you need to set a proxy for outgoing connections, uncomment the line below and set the
		// appropriate values.
		// ------------------------------------------------------------------------------------------
		//proxy = new Proxy(Proxy.Type.HTTP, new InetSocketAddress("10.1.1.10", 80));

		// ------------------------------------------------------------------------------------------
		// If your proxy requires authentication, uncomment the lines below and set the appropriate
		// values.
		// ------------------------------------------------------------------------------------------
		//Authenticator.setDefault(
		//	new Authenticator() {
		//		public PasswordAuthentication getPasswordAuthentication() {
		//			return new PasswordAuthentication("username", "password".toCharArray());
		//		}
		//	}
		//);

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

	public static Path getSampleDocPath() throws IOException {
		return new ClassPathResource("/static/SampleDocument.pdf").getFile().toPath();
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

	public static Path getSampleXmlPath() throws IOException {
		return new ClassPathResource("/static/SampleDocument.xml").getFile().toPath();
	}

	public static Path getSampleNFePath() throws IOException {
		return new ClassPathResource("/static/SampleNFe.xml").getFile().toPath();
	}

	public static byte[] getBatchDocContent(int id) throws IOException {
		Resource resource = new ClassPathResource("/static/" + String.format("%02d", id % 10) + ".pdf");
		InputStream fileStream = resource.getInputStream();
		ByteArrayOutputStream buffer = new ByteArrayOutputStream();
		org.apache.commons.io.IOUtils.copy(fileStream, buffer);
		fileStream.close();
		buffer.flush();
		return buffer.toByteArray();
	}

	public static Path getBatchDocPath(int id) throws IOException {
		return new ClassPathResource("/static/" + String.format("%02d", id % 10) + ".pdf").getFile().toPath();
	}

	public static byte[] getPdfStampContent() throws IOException {
		Resource resource = new ClassPathResource("/static/PdfStamp.png");
		InputStream fileStream = resource.getInputStream();
		ByteArrayOutputStream buffer = new ByteArrayOutputStream();
		IOUtils.copy(fileStream, buffer);
		fileStream.close();
		buffer.flush();
		return buffer.toByteArray();
	}

	public static byte[] getIcpBrasilLogoContent() throws IOException {
		Resource resource = new ClassPathResource("/static/icp-brasil.png");
		InputStream fileStream = resource.getInputStream();
		ByteArrayOutputStream buffer = new ByteArrayOutputStream();
		IOUtils.copy(fileStream, buffer);
		fileStream.close();
		buffer.flush();
		return buffer.toByteArray();
	}

	public static Certificate getSampleCertificateFromPKCS12() throws IOException, GeneralSecurityException {
		String alias = "{ecaf2712-4631-4f0e-94d1-fa6fcbff329f}";
		String password = "1234";

		Resource resource = new ClassPathResource("/static/Pierre de Fermat.pfx");
		InputStream fileStream = resource.getInputStream();
		KeyStore certStore = KeyStore.getInstance("pkcs12");
		certStore.load(fileStream, password.toCharArray());
		Certificate certificate = certStore.getCertificate(alias);
		fileStream.close();
		return certificate;
	}

	public static Key getSampleKeyFromPKCS12() throws IOException, GeneralSecurityException {
		String alias = "{ecaf2712-4631-4f0e-94d1-fa6fcbff329f}";
		String password = "1234";

		Resource resource = new ClassPathResource("/static/Pierre de Fermat.pfx");
		InputStream fileStream = resource.getInputStream();
		KeyStore certStore = KeyStore.getInstance("pkcs12");
		certStore.load(fileStream, password.toCharArray());
		Key pkey = certStore.getKey(alias, password.toCharArray());
		fileStream.close();
		return pkey;
	}

	public static byte[] getValidationResultIcon(boolean isValid) throws IOException {
		String filename = isValid ? "ok.png" : "not-ok.png";
		Resource resource = new ClassPathResource("/static/" + filename);
		InputStream fileStream = resource.getInputStream();
		ByteArrayOutputStream buffer = new ByteArrayOutputStream();
		org.apache.commons.io.IOUtils.copy(fileStream, buffer);
		fileStream.close();
		buffer.flush();
		return buffer.toByteArray();
	}

	public static Certificate getSampleCertificateFromMSCAPI() throws IOException, GeneralSecurityException {
		String alias = "Pierre de Fermat";
		String password = "";

		KeyStore certStore = KeyStore.getInstance("Windows-MY", "SunMSCAPI");
		certStore.load(null, password.toCharArray());
		return certStore.getCertificate(alias);
	}

	public static Key getSampleKeyFromMSCAPI() throws IOException, GeneralSecurityException {
		String alias = "Pierre de Fermat";
		String password = "";

		KeyStore certStore = KeyStore.getInstance("Windows-MY", "SunMSCAPI");
		certStore.load(null, password.toCharArray());
		return certStore.getKey(alias, password.toCharArray());
	}

	public static String joinStringsPt(List<String> strings) {
		StringBuilder text = new StringBuilder();
		int size = strings.size();
		int index = 0;
		for (String s : strings) {
			if (index > 0) {
				if (index < size - 1) {
					text.append(", ");
				} else {
					text.append(" e ");
				}
			}
			text.append(s);
			++index;
		}
		return text.toString();
	}

	/*
	 * ------------------------------------
	 * Configuration of the code generation
	 *
	 * - CodeSize   : size of the code in characters.
	 * - CodeGroups : number of groups to separate the code (must be a proper divisor of the code
	 *                size).
	 *
	 * Examples
	 * --------
	 *
	 * - CodeSize = 12, CodeGroups = 3 : XXXX-XXXX-XXXX
	 * - CodeSize = 12, CodeGroups = 4 : XXX-XXX-XXX-XXX
	 * - CodeSize = 16, CodeGroups = 4 : XXXX-XXXX-XXXX-XXXX
	 * - CodeSize = 20, CodeGroups = 4 : XXXXX-XXXXX-XXXXX-XXXXX
	 * - CodeSize = 20, CodeGroups = 5 : XXXX-XXXX-XXXX-XXXX-XXXX
	 * - CodeSize = 25, CodeGroups = 5 : XXXXX-XXXXX-XXXXX-XXXXX-XXXXX
	 *
	 * Entropy
	 * -------
	 *
	 * The resulting entropy of the code in bits is the size of the code times 5. Here are some
	 * suggestions:
	 *
	 * - 12 characters = 60 bits
	 * - 16 characters = 80 bits
	 * - 20 characters = 100 bits
	 * - 25 characters = 125 bits
	 */
	private static final int verificationCodeSize = 16;
	private static final int verificationCodeGroups = 4;

	// This method generates a verification code, without dashes.
	public static String generateVerificationCode() {
		// String with exactly 32 letters and numbers to be used on the codes. We recommend leaving
		// this value as is.
		final String alphabet = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
		// Allocate a byte array large enough to receive the necessary entropy.
		byte[] bytes = new byte[(int) Math.ceil(verificationCodeSize * 5 / 8.0)];
		// Generate the entropy with a cryptographic number generator.
		new Random().nextBytes(bytes);
		// Convert random bytes into bites.
		BitSet bits = BitSet.valueOf(bytes);
		// Iterate bits 5-by-5 converting into characters in our alphabet.
		StringBuilder sb = new StringBuilder();
		for (int i = 0; i < verificationCodeSize; i++) {
			int n = (bits.get(i) ? 1 : 0) << 4
				| (bits.get(i + 1) ? 1 : 0) << 3
				| (bits.get(i + 2) ? 1 : 0) << 2
				| (bits.get(i + 3) ? 1 : 0) << 1
				| (bits.get(i + 4) ? 1 : 0);
			sb.append(alphabet.charAt(n));
		}
		return sb.toString();
	}

	public static String formatVerificationCode(String code) {
		// Return the code separated in groups.
		int charsPerGroup = verificationCodeSize / verificationCodeGroups;
		List<String> groups = new ArrayList<String>();
		for (int i = 0; i < verificationCodeGroups; i++) {
			groups.add(code.substring(i * charsPerGroup, (i + 1) * charsPerGroup));
		}
		return String.join("-", groups);
	}

	public static String parseVerificationCode(String formattedCode) {
		if (formattedCode == null || formattedCode.length() <= 0) {
			return formattedCode;
		}
		return formattedCode.replaceAll("[^A-Za-z0-9]", "");
	}

}

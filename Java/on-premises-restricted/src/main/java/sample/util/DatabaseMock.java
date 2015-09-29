package sample.util;

import javax.servlet.http.HttpSession;
import java.io.IOException;
import java.io.OutputStream;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.util.UUID;

public class DatabaseMock {

	private HttpSession session;

	public DatabaseMock(HttpSession session) {
		this.session = session;
	}

	public String putSignedPdf(byte[] signedPdf) throws IOException {
		Path tempFilePath = Files.createTempFile(null, ".pdf");
		Files.write(tempFilePath, signedPdf);
		String signatureId = UUID.randomUUID().toString();
		session.setAttribute(signatureId, tempFilePath.toAbsolutePath().toString());
		return signatureId;
	}

	public byte[] getSignedPdf(String signatureId) throws IOException {
		String fileName = (String) session.getAttribute(signatureId);
		byte[] signedPdf = Files.readAllBytes(Paths.get(fileName));
		return signedPdf;
	}

}

package sample.util;


import javax.servlet.http.HttpSession;

public class StorageMock {

	/**
	 * Returns the verification code associated with the given document, or null if no verification
	 * code has been associated with it.
	 *
	 * @param session
	 * @param fileId
	 * @return
	 */
	public static String getVerificationCode(HttpSession session, String fileId) {
		// >>>>> NOTICE <<<<<
		// This should be implemented on your application as a SELECT on your "document table" by the
		// ID of the document, returning the value of the verification code column
		return (String) session.getAttribute(String.format("Files/%s/Code", fileId));
	}

	/**
	 * Registers the verification code for a given document.
	 *
	 * @param session
	 * @param fileId
	 * @param code
	 */
	public static void setVerificationCode(HttpSession session, String fileId, String code) {
		// >>>>> NOTICE <<<<<
		// This should be implemented on your application as an UPDATE on your "document table"
		// filling the verification code column, which should be an indexed column
		session.setAttribute(String.format("Files/%s/Code", fileId), code);
		session.setAttribute(String.format("Codes/%s", code), fileId);
	}

	/**
	 * Returns the ID of the document associated with a given verification code, or null if no
	 * document matcher the given code.
	 *
	 * @param session
	 * @param code
	 * @return
	 */
	public static String lookupVerificationCode(HttpSession session, String code) {
		if (code == null || code.length() == 0) {
			return null;
		}
		// >>>>> NOTICE <<<<<
		// This should be implemented on your application as a SELECT on your "document table" by the
		// verification code column, which should be an indexed column
		return (String) session.getAttribute(String.format("Codes/%s", code));
	}

}

package sample.models;

public class SignatureStartResponse {

	private boolean success;
	private String message;
	private String validationResults;
	private String token;
	private String toSignHash;
	private String digestAlgorithmOid;

	public SignatureStartResponse() {
	}

	public boolean isSuccess() {
		return success;
	}

	public void setSuccess(boolean success) {
		this.success = success;
	}

	public String getMessage() {
		return message;
	}

	public void setMessage(String message) {
		this.message = message;
	}

	public String getValidationResults() {
		return validationResults;
	}

	public void setValidationResults(String validationResults) {
		this.validationResults = validationResults;
	}

	public String getToken() {
		return token;
	}

	public void setToken(String token) {
		this.token = token;
	}

	public String getToSignHash() {
		return toSignHash;
	}

	public void setToSignHash(String toSignHash) {
		this.toSignHash = toSignHash;
	}

	public String getDigestAlgorithmOid() {
		return digestAlgorithmOid;
	}

	public void setDigestAlgorithmOid(String digestAlgorithmOid) {
		this.digestAlgorithmOid = digestAlgorithmOid;
	}
}

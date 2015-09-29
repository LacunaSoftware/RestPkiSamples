package sample.models;

public class SignatureCompleteRequest {

	private String token;
	private String signature;

	public SignatureCompleteRequest() {
	}

	public String getToken() {
		return token;
	}

	public void setToken(String token) {
		this.token = token;
	}

	public String getSignature() {
		return signature;
	}

	public void setSignature(String signature) {
		this.signature = signature;
	}
}

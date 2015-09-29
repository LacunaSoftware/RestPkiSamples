package sample.models;

public class SignatureCompleteResponse {
	private boolean success;
	private String message;
	private String validationResults;
	private String signatureId;

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

	public String getSignatureId() {
		return signatureId;
	}

	public void setSignatureId(String signatureId) {
		this.signatureId = signatureId;
	}
}

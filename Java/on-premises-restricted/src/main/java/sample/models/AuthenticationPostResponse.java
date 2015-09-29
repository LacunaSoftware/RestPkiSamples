package sample.models;

public class AuthenticationPostResponse {
	private boolean success;
	private String message;
	private String validationResults;

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
}

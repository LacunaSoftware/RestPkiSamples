<?php
namespace Lacuna;

class RestPkiClient {

	private $endpointUrl;
	private $authToken;

	public function __construct($endpointUrl, $authToken) {
		$this->endpointUrl = $endpointUrl;
		$this->authToken = $authToken;
    }
	
	public function getToken() {
		return $this->authToken;
	}

}

?>
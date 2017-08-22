<?php

/*
 * REST PKI client lib for PHP (legacy version for PHP 5.3 and 5.4)
 *
 * This file contains classes that encapsulate the calls to the REST PKI API.
 *
 * This file depends on the Httpful package, which in turn requires PHP 5.3 or greater.
 *
 * Notice: if you're using PHP version 5.5 or greater, please use one of the other samples, which make better use of
 * the extended capabilities of the newer versions of PHP:
 * https://github.com/LacunaSoftware/RestPkiSamples/tree/master/PHP
 */

namespace Lacuna;

require_once __DIR__ . '/vendor/autoload.php';

class RestPkiClient
{

    private $endpointUrl;
    private $accessToken;

    public function __construct($endpointUrl, $accessToken)
    {
        $this->endpointUrl = $endpointUrl;
        $this->accessToken = $accessToken;
    }

    public function get($url)
    {
        $verb = 'GET';
        $request = \Httpful\Request::get($this->endpointUrl . $url)
            ->expectsJson()
            ->addHeader('Authorization', 'Bearer ' . $this->accessToken);
        try {
            $httpResponse = $request->send();
        } catch (\Httpful\Exception\ConnectionErrorException $ex) {
            throw new RestUnreachableException($verb, $url, $ex);
        }
        $this->checkResponse($verb, $url, $httpResponse);
        return $httpResponse->body;
    }

    public function post($url, $data)
    {
        $verb = 'POST';
        $request = \Httpful\Request::post($this->endpointUrl . $url)
            ->expectsJson()
            ->addHeader('Authorization', 'Bearer ' . $this->accessToken);
        if (!is_null($data)) {
            $request->sendsJson()->body(json_encode($data));
        }
        try {
            $httpResponse = $request->send();
        } catch (\Httpful\Exception\ConnectionErrorException $ex) {
            throw new RestUnreachableException($verb, $url, $ex);
        }
        $this->checkResponse($verb, $url, $httpResponse);
        return $httpResponse->body;
    }

    private function checkResponse($verb, $url, \Httpful\Response $httpResponse)
    {
        $statusCode = $httpResponse->code;
        if ($statusCode < 200 || $statusCode > 299) {
            $ex = null;
            try {
                $response = $httpResponse->body;
                if ($statusCode == 422 && !empty($response->code)) {
                    if ($response->code == "ValidationError") {
                        $vr = new ValidationResults($response->validationResults);
                        $ex = new ValidationException($verb, $url, $vr);
                    } else {
                        $ex = new RestPkiException($verb, $url, $response->code, $response->detail);
                    }
                } else {
                    $ex = new RestErrorException($verb, $url, $statusCode, $response->message);
                }
            } catch (\Exception $e) {
                $ex = new RestErrorException($verb, $url, $statusCode);
            }
            throw $ex;
        }
    }

    public function getAuthentication()
    {
        return new Authentication($this);
    }
}

class RestException extends \Exception
{

    private $verb;
    private $url;

    public function __construct($message, $verb, $url, \Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->verb = $verb;
        $this->url = $url;
    }

    public function getVerb()
    {
        return $this->verb;
    }

    public function getUrl()
    {
        return $this->url;
    }

}

class RestUnreachableException extends RestException
{
    public function __construct($verb, $url, \Exception $previous)
    {
        parent::__construct("REST action {$verb} {$url} unreachable", $verb, $url, $previous);
    }
}

class RestErrorException extends RestException
{

    private $statusCode;
    private $errorMessage;

    public function __construct($verb, $url, $statusCode, $errorMessage = null)
    {
        $message = "REST action {$verb} {$url} returned HTTP error {$statusCode}";
        if (!empty($errorMessage)) {
            $message .= ": {$errorMessage}";
        }
        parent::__construct($message, $verb, $url);
        $this->statusCode = $statusCode;
        $this->errorMessage = $errorMessage;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}

class RestPkiException extends RestException
{

    private $errorCode;
    private $detail;

    public function __construct($verb, $url, $errorCode, $detail)
    {
        $message = "REST PKI action {$verb} {$url} error: {$errorCode}";
        if (!empty($detail)) {
            $message .= " ({$detail})";
        }
        parent::__construct($message, $verb, $url);
        $this->errorCode = $errorCode;
        $this->detail = $detail;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    public function getDetail()
    {
        return $this->detail;
    }
}

class ValidationException extends RestException
{

    /** @var ValidationResults */
    private $validationResults;

    public function __construct($verb, $url, ValidationResults $validationResults)
    {
        parent::__construct($validationResults->__toString(), $verb, $url);
        $this->validationResults = $validationResults;
    }

    public function getValidationResults()
    {
        return $this->validationResults;
    }
}

class Authentication
{

    /** @var RestPkiClient */
    private $restPkiClient;

    private $certificate;
    private $done;

    public function __construct($restPkiClient)
    {
        $this->restPkiClient = $restPkiClient;
        $this->done = false;
    }

    public function startWithWebPki($securityContextId)
    {
        $response = $this->restPkiClient->post('Api/Authentications', array(
            'securityContextId' => $securityContextId
        ));
        return $response->token;
    }

    public function completeWithWebPki($token)
    {
        $response = $this->restPkiClient->post("Api/Authentications/$token/Finalize", null);
        $this->certificate = $response->certificate;
        $this->done = true;
        return new ValidationResults($response->validationResults);
    }

    public function getCertificate()
    {
        if (!$this->done) {
            throw new \Exception('The method getCertificate() can only be called after calling the completeWithWebPki method');
        }
        return $this->certificate;
    }

}

abstract class SignatureStarter
{

    /** @var RestPkiClient */
    protected $restPkiClient;
    public $signerCertificate;
    public $signaturePolicyId;
    public $securityContextId;
    public $callbackArgument;
    protected $done;
    protected $certificateInfo;

    protected function __construct($restPkiClient)
    {
        $this->restPkiClient = $restPkiClient;
    }

    public function setSignerCertificate($certificate)
    {
        $this->signerCertificate = $certificate;
    }

    public function setSignaturePolicy($signaturePolicyId)
    {
        $this->signaturePolicyId = $signaturePolicyId;
    }

    public function setSecurityContext($securityContextId)
    {
        $this->securityContextId = $securityContextId;
    }

    public function setCallbackArgument($callbackArgument)
    {
        $this->callbackArgument = $callbackArgument;
    }

    public function getCertificateInfo()
    {
        if (!$this->done) {
            throw new \InvalidArgumentException("The getCertificateInfo() method can only be called after calling on of the Start methods");
        }

        return $this->certificateInfo;
    }

    public abstract function startWithWebPki();

}

class PadesSignatureStarter extends SignatureStarter
{

    public $pdfContent;
    public $measurementUnits;
    public $pageOptimization;
    public $bypassMarksIfSigned;
    public $visualRepresentation;
    public $pdfMarks;

    public function __construct($restPkiClient)
    {
        parent::__construct($restPkiClient);
        $this->bypassMarksIfSigned = true;
        $this->done = false;
        $this->pdfMarks = array();
    }

    public function setPdfToSignPath($pdfPath)
    {
        $this->pdfContent = file_get_contents($pdfPath);
    }

    public function setPdfToSignContent($content)
    {
        $this->pdfContent = $content;
    }

    public function setVisualRepresentation($visualRepresentation)
    {
        $this->visualRepresentation = $visualRepresentation;
    }

    public function startWithWebPki()
    {
        if (empty($this->pdfContent)) {
            throw new \Exception("The PDF to sign was not set");
        }
        if (empty($this->signaturePolicyId)) {
            throw new \Exception("The signature policy was not set");
        }

        $request = array(
            'signaturePolicyId' => $this->signaturePolicyId,
            'securityContextId' => $this->securityContextId,
            'callbackArgument' => $this->callbackArgument,
            'pdfMarks' => $this->pdfMarks,
            'bypassMarksIfSigned' => $this->bypassMarksIfSigned,
            'measurementUnits' => $this->measurementUnits,
            'pageOptimization' => $this->pageOptimization,
            'visualRepresentation' => $this->visualRepresentation
        );

        if (!empty($this->pdfContent)) {
            $request['pdfToSign'] = base64_encode($this->pdfContent);
        }
        if (!empty($this->signerCertificate)) {
            $request['certificate'] = base64_encode($this->signerCertificate);
        }

        $response = $this->restPkiClient->post('Api/PadesSignatures', $request);

        if (isset($response->certificate)) {
            $this->certificateInfo = $response->certificate;
        }
        $this->done = true;

        return $response->token;
    }

}

class CadesSignatureStarter extends SignatureStarter
{

    private $contentToSign;
    private $cmsToCoSign;
    private $encapsulateContent;

    public function __construct($restPkiClient)
    {
        parent::__construct($restPkiClient);
    }

    public function setFileToSign($filePath)
    {
        $this->contentToSign = file_get_contents($filePath);
    }

    public function setContentToSign($content)
    {
        $this->contentToSign = $content;
    }

    public function setCmsFileToSign($cmsPath)
    {
        $this->cmsToCoSign = file_get_contents($cmsPath);
    }

    public function setCmsToSign($cmsBytes)
    {
        $this->cmsToCoSign = $cmsBytes;
    }

    public function setEncapsulateContent($encapsulateContent)
    {
        $this->encapsulateContent = $encapsulateContent;
    }

    public function startWithWebPki()
    {

        if (empty($this->contentToSign) && empty($this->cmsToCoSign)) {
            throw new \Exception("The content to sign was not set and no CMS to be co-signed was given");
        }
        if (empty($this->signaturePolicyId)) {
            throw new \Exception("The signature policy was not set");
        }

        $request = array(
            'certificate' => $this->signerCertificate, // may be null
            'signaturePolicyId' => $this->signaturePolicyId,
            'securityContextId' => $this->securityContextId,
            'callbackArgument' => $this->callbackArgument,
            'encapsulateContent' => $this->encapsulateContent
        );

        if (!empty($this->contentToSign)) {
            $request['contentToSign'] = base64_encode($this->contentToSign);
        }
        if (!empty($this->cmsToCoSign)) {
            $request['cmsToCoSign'] = base64_encode($this->cmsToCoSign);
        }

        $response = $this->restPkiClient->post('Api/CadesSignatures', $request);

        if (isset($response->certificate)) {
            $this->certificateInfo = $response->certificate;
        }

        return $response->token;
    }

}

abstract class XmlSignatureStarter extends SignatureStarter
{

    protected $xmlContent;
    protected $signatureElementId;
    protected $signatureElementLocationXPath;
    protected $signatureElementLocationNsm;
    protected $signatureElementLocationInsertionOption;

    protected function __construct($restPkiClient)
    {
        parent::__construct($restPkiClient);
    }

    public function setXmlToSignPath($xmlPath)
    {
        $this->xmlContent = file_get_contents($xmlPath);
    }

    public function setXmlToSignContent($content)
    {
        $this->xmlContent = $content;
    }

    public function setSignatureElementLocation($xpath, $insertionOption, $namespaceManager = null)
    {
        $this->signatureElementLocationXPath = $xpath;
        $this->signatureElementLocationInsertionOption = $insertionOption;
        $this->signatureElementLocationNsm = $namespaceManager;
    }

    public function setSignatureElementId($signatureElementId)
    {
        $this->signatureElementId = $signatureElementId;
    }

    protected function verifyCommonParameters($isWithWebPki = false)
    {
        if (!$isWithWebPki) {
            if (empty($this->signerCertificate)) {
                throw new \Exception('The certificate was not set');
            }
        }
        if (empty($this->signaturePolicyId)) {
            throw new \Exception('The signature policy was not set');
        }
    }

    protected function getRequest()
    {
        $request = array(
            'signaturePolicyId' => $this->signaturePolicyId,
            'securityContextId' => $this->securityContextId,
            'signatureElementId' => $this->signatureElementId
        );
        if ($this->xmlContent != null) {
            $request['xml'] = base64_encode($this->xmlContent);
        }
        if ($this->signatureElementLocationXPath != null && $this->signatureElementLocationInsertionOption != null) {
            $request['signatureElementLocation'] = array(
                'xPath' => $this->signatureElementLocationXPath,
                'insertionOption' => $this->signatureElementLocationInsertionOption
            );

            if ($this->signatureElementLocationNsm != null) {
                $namespaces = array();

                foreach ($this->signatureElementLocationNsm as $key => $value) {
                    $namespaces[] = array(
                        'prefix' => $key,
                        'uri' => $value
                    );
                    $request['signatureElementLocation']['namespaces'] = $namespaces;
                }
            }
        }

        return $request;
    }
}

class XmlElementSignatureStarter extends XmlSignatureStarter
{

    private $toSignElementId;
    /** @var XmlIdResolutionTable */
    private $idResolutionTable;

    public function __construct($restPkiClient)
    {
        parent::__construct($restPkiClient);
    }

    public function setToSignElementId($toSignElementId)
    {
        $this->toSignElementId = $toSignElementId;
    }

    public function setIdResolutionTable(XmlIdResolutionTable $idResolutionTable)
    {
        $this->idResolutionTable = $idResolutionTable;
    }

    public function startWithWebPki()
    {
        parent::verifyCommonParameters(true);

        if (empty($this->xmlContent)) {
            throw new \Exception('The XML was not set');
        }
        if (empty($this->toSignElementId)) {
            throw new \Exception('The XML element Id to sign was not set');
        }

        $request = parent::getRequest();
        $request['elementToSignId'] = $this->toSignElementId;
        if ($this->idResolutionTable != null) {
            $request['idResolutionTable'] = $this->idResolutionTable->toModel();
        }

        $response = $this->restPkiClient->post('Api/XmlSignatures/XmlElementSignature', $request);
        return $response->token;
    }
}

class FullXmlSignatureStarter extends XmlSignatureStarter
{

    public function __construct($restPkiClient)
    {
        parent::__construct($restPkiClient);
    }

    public function startWithWebPki()
    {
        parent::verifyCommonParameters(true);

        if (empty($this->xmlContent)) {
            throw new \Exception('The XML was not set');
        }

        $request = parent::getRequest();

        $response = $this->restPkiClient->post('Api/XmlSignatures/FullXmlSignature', $request);
        return $response->token;
    }
}

abstract class SignatureFinisher
{
    /** @var RestPkiClient */
    protected $restPkiClient;
    public $token;
    public $signature;
    protected $done;
    protected $callbackArgument;
    protected $certificateInfo;

    public function __construct($restPkiClient)
    {
        $this->restPkiClient = $restPkiClient;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function setSignature($signature)
    {
        $this->signature = $signature;
    }

    public abstract function finish();

    public function getCallbackArgument()
    {
        if (!$this->done) {
            throw new \InvalidArgumentException('The method getCallbackArgument() can only be called after calling the finish() method');
        }

        return $this->callbackArgument;
    }

    public function getCertificateInfo()
    {
        if (!$this->done) {
            throw new \InvalidArgumentException('The method getCertificateInfo() can only be called after calling the finish() method');
        }

        return $this->certificateInfo;
    }
}

class PadesSignatureFinisher extends SignatureFinisher
{
    private $signedPdf;

    public function __construct($restPkiClient)
    {
        parent::__construct($restPkiClient);
    }

    public function finish()
    {
        $request = null;

        if (empty($this->token)) {
            throw new \Exception("The token was not set");
        }

        if (empty($this->signature)) {
            $response = $this->restPkiClient->post("Api/PadesSignatures/{$this->token}/Finalize", null);
        } else {
            $request['signature'] = $this->signature;
            $response = $this->restPkiClient->post("Api/PadesSignatures/{$this->token}/Finalize", $request);
        }

        $this->signedPdf = base64_decode($response->signedPdf);
        $this->callbackArgument = $response->callbackArgument;
        $this->certificateInfo = $response->certificate;
        $this->done = true;

        return $this->signedPdf;
    }

    public function getSignedPdf()
    {
        if (!$this->done) {
            throw new \Exception('The getSignedPdf() method can only be called after calling one of the Finish methods');
        }

        return $this->signedPdf;
    }

    public function writeSignedPdfToPath($pdfPath)
    {
        if (!$this->done) {
            throw new \Exception('The method writeSignedPdfToPath() can only be called after calling the finish() method');
        }

        file_put_contents($pdfPath, $this->signedPdf);
    }
}

class CadesSignatureFinisher extends SignatureFinisher
{
    private $cms;

    public function __construct($restPkiClient)
    {
        parent::__construct($restPkiClient);
    }

    public function finish()
    {
        $request = null;

        if (empty($this->token)) {
            throw new \Exception("The token was not set");
        }

        if (empty($this->signature)) {
            $response = $this->restPkiClient->post("Api/CadesSignatures/{$this->token}/Finalize", null);
        } else {
            $request['signature'] = $this->signature;
            $response = $this->restPkiClient->post("Api/CadesSignatures/{$this->token}/Finalize", $request);
        }

        $this->cms = base64_decode($response->cms);
        $this->callbackArgument = $response->callbackArgument;
        $this->certificateInfo = $response->certificate;
        $this->done = true;

        return $this->cms;
    }

    public function getCms()
    {
        if (!$this->done) {
            throw new \InvalidArgumentException("The getCms() method can only be called after calling one of the Finish method");
        }

        return $this->cms;
    }

    public function writeCmsfToPath($path)
    {
        if (!$this->done) {
            throw new \Exception('The method writeCmsfToPath() can only be called after calling the finish() method');
        }

        file_put_contents($path, $this->cms);
    }
}

class XmlSignatureFinisher extends SignatureFinisher
{
    private $signedXml;

    public function __construct($restPkiClient)
    {
        parent::__construct($restPkiClient);
    }

    public function finish()
    {

        if (empty($this->token)) {
            throw new \Exception("The token was not set");
        }

        $response = $this->restPkiClient->post("Api/XmlSignatures/{$this->token}/Finalize", null);

        $this->signedXml = base64_decode($response->signedXml);
        $this->certificateInfo = $response->certificate;
        $this->done = true;

        return $this->signedXml;
    }

    public function writeSignedXmlToPath($xmlPath)
    {
        if (!$this->done) {
            throw new \Exception('The method writeSignedXmlToPath() can only be called after calling the finish() method');
        }

        file_put_contents($xmlPath, $this->signedXml);
    }
}

abstract class SignatureExplorer
{
    /** @var RestPkiClient */
    protected $restPkiClient;
    protected $signatureFileContent;
    protected $validate;
    protected $defaultSignaturePolicyId;
    protected $acceptableExplicitPolicies;
    protected $securityContextId;

    protected function __construct($restPkiClient)
    {
        $this->restPkiClient = $restPkiClient;
    }

    public function setSignatureFile($filePath)
    {
        $this->signatureFileContent = file_get_contents($filePath);
    }

    public function setValidate($validate)
    {
        $this->validate = $validate;
    }

    public function setDefaultSignaturePolicyId($signaturePolicyId)
    {
        $this->defaultSignaturePolicyId = $signaturePolicyId;
    }

    public function setAcceptableExplicitPolicies($policyCatalog)
    {
        $this->acceptableExplicitPolicies = $policyCatalog;
    }

    public function setSecurityContextId($securityContextId)
    {
        $this->securityContextId = $securityContextId;
    }

    protected function getRequest($mimeType)
    {
        $request = array(
            "validate" => $this->validate,
            "defaultSignaturePolicyId" => $this->defaultSignaturePolicyId,
            "securityContextId" => $this->securityContextId,
            "acceptableExplicitPolicies" => $this->acceptableExplicitPolicies,
            "file" => null
        );

        if ($this->signatureFileContent != null) {
            $request['file'] = array(
                "content" => base64_encode($this->signatureFileContent),
                "mimeType" => $mimeType,
                "blobId" => null
            );
        }

        return $request;
    }
}

class PadesSignatureExplorer extends SignatureExplorer
{
    const PDF_MIME_TYPE = "application/pdf";

    public function __construct($client)
    {
        parent::__construct($client);
    }

    public function open()
    {
        if (!isset($this->signatureFileContent)) {
            throw new \RuntimeException("The signature file to open not set");
        } else {
            $request = $this->getRequest($this::PDF_MIME_TYPE);
            $response = $this->restPkiClient->post("Api/PadesSignatures/Open", $request);

            foreach ($response->signers as $signer) {
                $signer->validationResults = new ValidationResults($signer->validationResults);
                $signer->messageDigest->algorithm = DigestAlgorithm::getInstanceByApiAlgorithm(
                    $signer->messageDigest->algorithm
                );

                if (isset($signer->signingTime)) {
                    $signer->signingTime = date("d/m/Y H:i:s P", strtotime($signer->signingTime));
                }
                if (isset($signer->certificate)) {
                    if (isset($signer->certificate->pkiBrazil)) {

                        if (isset($signer->certificate->pkiBrazil->cpf)) {
                            $cpf = $signer->certificate->pkiBrazil->cpf;
                            $signer->certificate->pkiBrazil->cpfFormatted = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3)
                                . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9);
                        } else {
                            $signer->certificate->pkiBrazil->cpfFormatted = '';
                        }

                        if (isset($signer->certificate->pkiBrazil->cnpj)) {
                            $cnpj = $signer->certificate->pkiBrazil->cnpj;
                            $signer->certificate->pkiBrazil->cnpjFormatted = substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3)
                                . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12);
                        } else {
                            $signer->certificate->pkiBrazil->cnpjFormatted = '';
                        }
                    }
                }
            }

            return $response;
        }
    }
}

class CadesSignatureExplorer extends SignatureExplorer
{
    const CMS_SIGNATURE_MIME_TYPE = "application/pkcs7-signature";

    private $dataFileContent;

    public function __construct($client) {
        parent::__construct($client);
    }

    public function setDataFile($filePath)
    {
        $this->dataFileContent = file_get_contents($filePath);
    }

    public function open() {
        $dataHashes = null;

        if (!isset($this->signatureFileContent)) {
            throw new \RuntimeException("The signature file to open not set");
        }
        
        if($this->dataFileContent != null) {
            $requiredHashes = $this->getRequiredHashes();
            if (count($requiredHashes) > 0) {
                $dataHashes = $this->computeDataHashes($this->dataFileContent, $requiredHashes);
            }
        }

        $request = $this->getRequest(self::CMS_SIGNATURE_MIME_TYPE);
        $request['dataHashes'] = $dataHashes;

        $response = $this->restPkiClient->post("Api/CadesSignatures/Open", $request);

        foreach ($response->signers as $signer) {
            $signer->validationResults = new ValidationResults($signer->validationResults);
            $signer->messageDigest->algorithm = DigestAlgorithm::getInstanceByApiAlgorithm(
                $signer->messageDigest->algorithm
            );

            if (isset($signer->signingTime)) {
                $signer->signingTime = date("d/m/Y H:i:s P", strtotime($signer->signingTime));
            }
            if (isset($signer->certificate)) {
                if (isset($signer->certificate->pkiBrazil)) {

                    if (isset($signer->certificate->pkiBrazil->cpf)) {
                        $cpf = $signer->certificate->pkiBrazil->cpf;
                        $signer->certificate->pkiBrazil->cpfFormatted = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3)
                            . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9);
                    } else {
                        $signer->certificate->pkiBrazil->cpfFormatted = '';
                    }

                    if (isset($signer->certificate->pkiBrazil->cnpj)) {
                        $cnpj = $signer->certificate->pkiBrazil->cnpj;
                        $signer->certificate->pkiBrazil->cnpjFormatted = substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3)
                            . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12);
                    } else {
                        $signer->certificate->pkiBrazil->cnpjFormatted = '';
                    }
                }
            }
        }

        return $response;
    }

    private function getRequiredHashes()
    {
        $request = array(
            "content" => base64_encode($this->signatureFileContent),
            "mimeType" => self::CMS_SIGNATURE_MIME_TYPE
        );

        $response = $this->restPkiClient->post("Api/CadesSignatures/RequiredHashes", $request);

        $algs = array();

        foreach ($response as $alg) {
            array_push($algs, DigestAlgorithm::getInstanceByApiAlgorithm($alg));
        }

        return $algs;
    }

    private function computeDataHashes($dataFileStream, $algorithms)
    {
        $dataHashes = array();

        foreach ($algorithms as $algorithm) {
            $digestValue = mhash($algorithm->getHashId(), $dataFileStream);
            $dataHash = array(
                'algorithm' => $algorithm->getAlgorithm(),
                'value' => base64_encode($digestValue)
            );
            array_push($dataHashes, $dataHash);
        }

        return $dataHashes;
    }
}

class StandardSecurityContexts
{
    const PKI_BRAZIL = '201856ce-273c-4058-a872-8937bd547d36';
    const PKI_ITALY = 'c438b17e-4862-446b-86ad-6f85734f0bfe';
    const WINDOWS_SERVER = '3881384c-a54d-45c5-bbe9-976b674f5ec7';
}

class StandardSignaturePolicies
{
    const PADES_BASIC = '78d20b33-014d-440e-ad07-929f05d00cdf';
    const PADES_ICPBR_ADR_BASICA = '531d5012-4c0d-4b6f-89e8-ebdcc605d7c2';
    const PADES_ICPBR_ADR_TEMPO = '10f0d9a5-a0a9-42e9-9523-e181ce05a25b';
    const CADES_BES = 'a4522485-c9e5-46c3-950b-0d6e951e17d1';
    const CADES_ICPBR_ADR_BASICA = '3ddd8001-1672-4eb5-a4a2-6e32b17ddc46';
    const CADES_ICPBR_ADR_TEMPO = 'a5332ad1-d105-447c-a4bb-b5d02177e439';
    const CADES_ICPBR_ADR_VALIDACAO = '92378630-dddf-45eb-8296-8fee0b73d5bb';
    const CADES_ICPBR_ADR_COMPLETA = '30d881e7-924a-4a14-b5cc-d5a1717d92f6';
    const XML_XADES_BES = '1beba282-d1b6-4458-8e46-bd8ad6800b54';
    const XML_DSIG_BASIC = '2bb5d8c9-49ba-4c62-8104-8141f6459d08';
    const XML_ICPBR_NFE_PADRAO_NACIONAL = 'a3c24251-d43a-4ba4-b25d-ee8e2ab24f06';
    const XML_ICPBR_ADR_BASICA = '1cf5db62-58b6-40ba-88a3-d41bada9b621';
    const XML_ICPBR_ADR_TEMPO = '5aa2e0af-5269-43b0-8d45-f4ef52921f04';
}

class StandardSignaturePolicyCatalog
{
    protected $policies;

    public function __construct($policies)
    {
        $this->policies = $policies;
    }

    public static function getPkiBrazilCades()
    {
        return array(
            StandardSignaturePolicies::CADES_ICPBR_ADR_BASICA,
            StandardSignaturePolicies::CADES_ICPBR_ADR_TEMPO,
            StandardSignaturePolicies::CADES_ICPBR_ADR_COMPLETA
        );
    }

    public static function getPkiBrazilCadesWithSignerCertificateProtection()
    {
        return array(
            StandardSignaturePolicies::CADES_ICPBR_ADR_TEMPO,
            StandardSignaturePolicies::CADES_ICPBR_ADR_COMPLETA
        );
    }

    public static function getPkiBrazilCadesWithCACertificateProtection()
    {
        return array(
            StandardSignaturePolicies::CADES_ICPBR_ADR_COMPLETA
        );
    }

    public static function getPkiBrazilPades()
    {
        return array(
            StandardSignaturePolicies::PADES_ICPBR_ADR_BASICA,
            StandardSignaturePolicies::PADES_ICPBR_ADR_TEMPO
        );
    }

    public static function getPkiBrazilPadesWithSignerCertificateProtection()
    {
        return array(
            StandardSignaturePolicies::PADES_ICPBR_ADR_TEMPO
        );
    }
}

class PadesMeasurementUnits
{
    const CENTIMETERS = "Centimeters";
    const PDFPOINTS = "PdfPoints";
}

class PadesVisualPositioningPresets
{
    private static $cachedPresets = array();

    public static function getFootnote(RestPkiClient $restPkiClient, $pageNumber = null, $rows = null)
    {
        $urlSegment = 'Footnote';
        if (!empty($pageNumber)) {
            $urlSegment .= "?pageNumber=" . $pageNumber;
        }
        if (!empty($rows)) {
            $urlSegment .= "?rows=" . $rows;
        }
        return self::getPreset($restPkiClient, $urlSegment);
    }

    public static function getNewPage(RestPkiClient $restPkiClient)
    {
        return self::getPreset($restPkiClient, 'NewPage');
    }

    private static function getPreset(RestPkiClient $restPkiClient, $urlSegment)
    {
        if (array_key_exists($urlSegment, self::$cachedPresets)) {
            return self::$cachedPresets[$urlSegment];
        }
        $preset = $restPkiClient->get("Api/PadesVisualPositioningPresets/$urlSegment");
        self::$cachedPresets[$urlSegment] = $preset;
        return $preset;
    }
}

class XmlInsertionOptions
{
    const APPEND_CHILD = 'AppendChild';
    const PREPEND_CHILD = 'PrependChild';
    const APPEND_SIBLING = 'AppendSibling';
    const PREPEND_SIBLING = 'PrependSibling';
}

class XmlIdResolutionTable
{
    private $model;

    public function __construct($includeXmlIdGlobalAttribute = null)
    {
        $this->model = array(
            'elementIdAttributes' => array(),
            'globalIdAttributes' => array(),
            'includeXmlIdAttribute' => $includeXmlIdGlobalAttribute
        );
    }

    public function addGlobalIdAttribute($idAttributeLocalName, $idAttributeNamespace = null)
    {
        $this->model['globalIdAttributes'][] = array(
            'localName' => $idAttributeLocalName,
            'namespace' => $idAttributeNamespace
        );
    }

    public function setElementIdAttribute(
        $elementLocalName,
        $elementNamespace,
        $idAttributeLocalName,
        $idAttributeNamespace = null
    ) {
        $this->model['elementIdAttributes'][] = array(
            'element' => array(
                'localName' => $elementLocalName,
                'namespace' => $elementNamespace
            ),
            'attribute' => array(
                'localName' => $idAttributeLocalName,
                'namespace' => $idAttributeNamespace
            )
        );
    }

    public function toModel()
    {
        return $this->model;
    }
}

class ValidationResults
{
    private $errors;
    private $warnings;
    private $passedChecks;

    public function __construct($model)
    {
        $this->errors = self::convertItems($model->errors);
        $this->warnings = self::convertItems($model->warnings);
        $this->passedChecks = self::convertItems($model->passedChecks);
    }

    public function isValid()
    {
        return empty($this->errors);
    }

    public function getChecksPerformed()
    {
        return count($this->errors) + count($this->warnings) + count($this->passedChecks);
    }

    public function hasErrors()
    {
        return !empty($this->errors);
    }

    public function hasWarnings()
    {
        return !empty($this->warnings);
    }

    public function __toString()
    {
        return $this->toString(0);
    }

    public function toString($indentationLevel)
    {
        $tab = str_repeat("\t", $indentationLevel);
        $text = '';
        $text .= $this->getSummary($indentationLevel);
        if ($this->hasErrors()) {
            $text .= "\n{$tab}Errors:\n";
            $text .= self::joinItems($this->errors, $indentationLevel);
        }
        if ($this->hasWarnings()) {
            $text .= "\n{$tab}Warnings:\n";
            $text .= self::joinItems($this->warnings, $indentationLevel);
        }
        if (!empty($this->passedChecks)) {
            $text .= "\n{$tab}Passed checks:\n";
            $text .= self::joinItems($this->passedChecks, $indentationLevel);
        }
        return $text;
    }

    public function getSummary($indentationLevel = 0)
    {
        $tab = str_repeat("\t", $indentationLevel);
        $text = "{$tab}Validation results: ";
        if ($this->getChecksPerformed() === 0) {
            $text .= 'no checks performed';
        } else {
            $text .= "{$this->getChecksPerformed()} checks performed";
            if ($this->hasErrors()) {
                $text .= ', ' . count($this->errors) . ' errors';
            }
            if ($this->hasWarnings()) {
                $text .= ', ' . count($this->warnings) . ' warnings';
            }
            if (!empty($this->passedChecks)) {
                if (!$this->hasErrors() && !$this->hasWarnings()) {
                    $text .= ", all passed";
                } else {
                    $text .= ', ' . count($this->passedChecks) . ' passed';
                }
            }
        }
        return $text;
    }

    private static function convertItems($items)
    {
        $converted = array();
        foreach ($items as $item) {
            $converted[] = new ValidationItem($item);
        }
        return $converted;
    }

    private static function joinItems($items, $indentationLevel)
    {
        $text = '';
        $isFirst = true;
        $tab = str_repeat("\t", $indentationLevel);
        foreach ($items as $item) {
            /** @var ValidationItem $item */
            if ($isFirst) {
                $isFirst = false;
            } else {
                $text .= "\n";
            }
            $text .= "{$tab}- ";
            $text .= $item->toString($indentationLevel);
        }
        return $text;
    }

}

class ValidationItem
{
    private $type;
    private $message;
    private $detail;
    /** @var ValidationResults */
    private $innerValidationResults;

    public function __construct($model)
    {
        $this->type = $model->type;
        $this->message = $model->message;
        $this->detail = $model->detail;
        if ($model->innerValidationResults !== null) {
            $this->innerValidationResults = new ValidationResults($model->innerValidationResults);
        }
    }

    public function getType()
    {
        return $this->type;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getDetail()
    {
        return $this->detail;
    }

    public function __toString()
    {
        return $this->toString(0);
    }

    public function toString($indentationLevel)
    {
        $text = '';
        $text .= $this->message;
        if (!empty($this->detail)) {
            $text .= " ({$this->detail})";
        }
        if ($this->innerValidationResults !== null) {
            $text .= "\n";
            $text .= $this->innerValidationResults->toString($indentationLevel + 1);
        }
        return $text;
    }

}

class DigestAlgorithm
{
    const MD5 = 'MD5';
    const SHA1 = 'SHA-1';
    const SHA256 = 'SHA-256';
    const SHA384 = 'SHA-384';
    const SHA512 = 'SHA-512';

    private $name;
    private $algorithm;

    private function __construct($name)
    {
        $this->name = constant('Lacuna\DigestAlgorithm::' . $name);
        $this->algorithm = $name;
    }

    public static function getInstanceByApiAlgorithm($algorithm)
    {
        if (defined('Lacuna\DigestAlgorithm::' . $algorithm)) {
            return new DigestAlgorithm($algorithm);
        } else {
            throw new \RuntimeException("Unsupported digest algorithm: " . $algorithm); // should not happen
        }
    }

    public function getHashId()
    {
        switch ($this->algorithm) {
            case 'MD5':
                return MHASH_MD5;
            case 'SHA1':
                return MHASH_SHA1;
            case 'SHA256':
                return MHASH_SHA256;
            case 'SHA384':
                return MHASH_SHA384;
            case 'SHA512':
                return MHASH_SHA512;
            default:
                throw new \RuntimeException("Could not get MessageDigest instance for algorithm " . $this->algorithm);
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function getAlgorithm()
    {
        return $this->algorithm;
    }
}

class Color
{
    public $alpha;
    public $blue;
    public $green;
    public $red;

    public function __construct()
    {
        $args = func_get_args();
        if (sizeof($args) == 1 && is_string($args[0]) && strlen($args[0]) == 7) { // Case "#RRGGBB"
            $this->red = hexdec(substr($args[0], 1, 2));
            $this->green = hexdec(substr($args[0], 3, 2));
            $this->blue = hexdec(substr($args[0], 5, 2));
            $this->alpha = 100;
        } else {
            if (sizeof($args) == 2 && is_string($args[0]) && strlen($args[0]) == 7) { // Case ("#RRGGBB", a)
                $this->red = hexdec(substr($args[0], 1, 2));
                $this->green = hexdec(substr($args[0], 3, 2));
                $this->blue = hexdec(substr($args[0], 5, 2));
                $this->alpha = $args[1];
            } else {
                if (sizeof($args) == 3) { // Case (r, g, b)
                    $this->red = $args[0];
                    $this->green = $args[1];
                    $this->blue = $args[2];
                    $this->alpha = 100;
                } else {
                    if (sizeof($args) == 4) { // Case (r, g, b, a)
                        $this->red = $args[0];
                        $this->green = $args[1];
                        $this->blue = $args[2];
                        $this->alpha = $args[3];
                    } else {
                        throw new \InvalidArgumentException("Invalid parameters passed to the Color's Constructor");
                    }
                }
            }
        }

        if (!is_int($this->red) || !is_int($this->green) || !is_int($this->blue) || (isset($this->alpha) && !is_int($this->alpha))) {
            throw new \InvalidArgumentException("Invalid parameters passed to the Color's Constructor");
        }
    }
}

class PdfMarker
{
    /** @var RestPkiClient */
    private $client;

    public $measurementUnits;
    public $pageOptimization;
    public $abortIfSigned;
    public $marks;
    public $fileContent;

    public function __construct($client)
    {
        $this->client = $client;
        $this->marks = array();
        $this->measurementUnits = PadesMeasurementUnits::CENTIMETERS;
    }

    public function setFileFromPath($path)
    {
        $this->fileContent = file_get_contents($path);
    }

    public function setFileFromContentRaw($contentRaw)
    {
        $this->fileContent = $contentRaw;
    }

    public function setFileFromContentBase64($contentBase64)
    {
        $this->fileContent = base64_decode($contentBase64);
    }

    public function apply()
    {
        $request = array(
            'marks' => $this->marks,
            'measurementUnits' => $this->measurementUnits,
            'pageOptimization' => $this->pageOptimization,
            'abortIfSigned' => $this->abortIfSigned
        );
        $request['file'] = array(
            'content' => base64_encode($this->fileContent)
        );
        $response = $this->client->post('Api/Pdf/AddMarks', $request);
        return $response->file;
    }
}

class PdfTextStyle
{
    const NORMAL = "Normal";
    const BOLD = "Bold";
    const ITALIC = "Italic";
}

class PdfMarkElementType
{
    const TEXT = "Text";
    const IMAGE = "Image";
    const QRCODE = "QRCode";
}

class PdfMarkPageOptions
{
    const ALL_PAGES = 'AllPages';
    const SINGLE_PAGE = 'SinglePage';
    const SINGLE_PAGE_FROM_END = 'SinglePageFromEnd';
    const NEW_PAGE = 'NewPage';
}

class PdfMark
{
    public $container;
    public $borderWidth;
    public $borderColor;
    public $backgroundColor;
    public $elements;
    public $pageOption;
    public $pageOptionNumber;

    public function __construct()
    {
        $this->borderWidth = 0.0;
        $this->borderColor = new Color("#000000"); // Black
        $this->backgroundColor = new Color("#FFFFFF", 0); // Transparent
        $this->elements = array();
        $this->pageOption = PdfMarkPageOptions::ALL_PAGES;
    }
}

class PdfMarkElement
{
    public $elementType;
    public $relativeContainer;
    public $rotation;
    public $opacity;

    public function __construct($elementType, $relativeContainer = null)
    {
        $this->rotation = 0;
        $this->elementType = $elementType;
        $this->relativeContainer = $relativeContainer;
        $this->opacity = 100;
    }
}

class PdfMarkTextElement extends PdfMarkElement
{
    public $textSections;
    public $align;

    public function __construct($relativeContainer = null, $textSections = null)
    {
        parent::__construct(PdfMarkElementType::TEXT, $relativeContainer);
        if (empty($textSections)) {
            $this->textSections = array();
        } else {
            $this->textSections = $textSections;
        }
        $this->align = 'Left';
    }
}

class PdfMarkImageElement extends PdfMarkElement
{
    public $image;

    public function __construct($relativeContainer = null, $image = null)
    {
        parent::__construct(PdfMarkElementType::IMAGE, $relativeContainer);
        $this->image = $image;
    }
}

class PdfMarkQRCodeElement extends PdfMarkElement
{
    public $qrCodeData;
    public $drawQuietZones;

    public function __construct($relativeContainer = null, $qrCodeData = null)
    {
        parent::__construct(PdfMarkElementType::QRCODE, $relativeContainer);
        $this->qrCodeData = $qrCodeData;
    }
}

class PdfTextSection
{
    public $style;
    public $text;
    public $color;
    public $fontSize;

    public function __construct($text = null, $color = null, $fontSize = null)
    {
        $this->style = PdfTextStyle::NORMAL;
        $this->text = $text;
        $this->fontSize = $fontSize;
        if (empty($color)) {
            $this->color = new Color('#000000'); // Black
        } else {
            $this->color = $color;
        }
    }
}

class PdfMarkImage
{
    public $resource;
    public $opacity;

    public function __construct($imageContent = null, $mimeType = null)
    {
        $this->resource = new ResourceContentOrReference();
        if (!empty($imageContent)) {
            $this->resource->content = base64_encode($imageContent);
        }
        if (!empty($mimeType)) {
            $this->resource->mimeType = $mimeType;
        }
    }
}

class ResourceContentOrReference
{
    public $url;
    public $mimeType;
    public $content;

    public function __construct()
    {
    }
}
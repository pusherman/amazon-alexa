<?php
  
namespace Pusherman\Alexa;

use Pusherman\Alexa\Certifcate;
use \DateTime;
use \Exception;

class Request
{
  const TIMESTAMP_TOLERNANCE = 150;

  function __construct() {
    $this->bodyRaw = file_get_contents('php://input');
    $this->body = json_decode($this->bodyRaw);
  }
  
  /**
   * Validate based on Amazon's guidelines
   *
   * https://developer.amazon.com/public/solutions/alexa/alexa-skills-kit/docs/developing-an-alexa-skill-as-a-web-service
   */
  public function validate($appId) {
    $chainURL = $_SERVER['HTTP_SIGNATURECERTCHAINURL'];
    
    $this->validatechainURL($chainURL)
         ->validateAppId($appId)
         ->validateTimestamp();
    
    $this->certificate = (new Certificate())
      ->fetch($chainURL)
      ->validate();
    
    $this->validateSignature();
  }
  
  /**
   * Validate the chain URL
   */
  private function validatechainURL($chainURL) {
  	$parsedUrl = parse_url($chainURL);
  	
  	if (strcasecmp($parsedUrl['scheme'], 'https') !== 0) {
  		throw new Exception("The chain URL protocol must be equal to https");
    }
    
  	if (strcasecmp($parsedUrl['host'], 's3.amazonaws.com') !== 0) {
  		throw new Exception("The chain URL hostname must be equal to s3.amazonaws.com");
    }
  
  	if (strpos($parsedUrl['path'], '/echo.api/') === false) {
  		throw new Exception("The chain URL path must start with /echo.api/");
    }
  
  	if (array_key_exists('port', $parsedUrl) && $parsedUrl['port'] !== 443) {
  		throw new Exception("If the chain URL port is defined in the URL, it must be equal to 443");
    }
    
    return $this;
  }
  
  /**
   * Validate the app id in the request
   */
  private function validateAppId($appId) {
    if ($this->body->session->application->applicationId !== $appId) {
      throw new Exception("Unknown application ID");
    }

    return $this;
  }

  /**
   * Validate the request timestamp
   */
  private function validateTimestamp() {
    $requestTime = $this->body->request->timestamp;
    
    $requestTimestamp = (new DateTime($requestTime))->getTimestamp();
    $currentTimestamp = (new DateTime())->getTimestamp();
    
    $secondsBetween = $currentTimestamp - $requestTimestamp;
    
    if ($secondsBetween >= self::TIMESTAMP_TOLERNANCE) {
      throw new Exception("Timestamp older than TIMESTAMP_TOLERANCE");
    }
    
    return $this;
  }

  /**
   * Validate the request signature with the certs public key
   */
  private function validateSignature() {
    $signature = $_SERVER['HTTP_SIGNATURE'];
    $publicKey = $this->certificate->getPublicKey();

    if (openssl_verify($this->bodyRaw, base64_decode($signature), $publicKey) !== 1) {
      throw new Exception('Invalid request signature');
    }

    return $this;
  }
}

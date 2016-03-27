<?php
  
namespace Pusherman\Alexa;

use DateTime;

class Certificate
{
  const ECHO_SAN = 'echo-api.amazon.com';

  /**
   * Download and parse the cert
   */
  public function fetch($url) {
    $this->bodyRaw = file_get_contents($url);
    $this->body = openssl_x509_parse($this->bodyRaw);
    
    return $this;
  }

  /**
   * Validate the certifcate
   */
  public function validate() {
    $this->validateExpDate()
         ->validateSAN();
         
    return $this;
  }

  /**
   * Get the public key from the cert chain
   */ 
  public function getPublicKey() {
    return openssl_pkey_get_public($this->bodyRaw);
  }

  /**
   * Make sure the certs expiration date is valid
   */  
  private function validateExpDate() {
    $now = (new DateTime())->getTimestamp();
  
    if ($this->body['validFrom_time_t'] <= $now === false) {
      throw new Exception('Cert not valid yet');
    }
    
    if ($now <= $this->body['validTo_time_t'] === false) {
      throw new Exception('Cert expired');
    }
    
    return $this;
  }

  /**
   * Validate the cert SAN
   */
  private function validateSAN() {
    if (strpos($this->body['extensions']['subjectAltName'], self::ECHO_SAN) === false) {
      throw new Exception('Invalid certificate SAN');
    }

    return $this;
  }
  
}
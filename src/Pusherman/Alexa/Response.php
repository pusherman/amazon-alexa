<?php
  
namespace Pusherman\Alexa;

class Response
{ 
  
  public function __construct() {
    $this->appVersion = '0.1';  
  }

  /**
   * Add a text response to send
   */
  public function say($text) {
    $this->responseText = $text;
  }

  /**
   * Send the response
   */
  public function send($endSession = true) {
    $speach = [
      'type' => 'PlainText',
      'text' => $this->responseText
    ];

    $response = [
      'version' => $this->appVersion,
      'response' => ['outputSpeech' => $speach],
      'shouldEndSession' => $endSession
    ];
    
    header('Content-Type: application/json');
    print json_encode($response);
  }
}

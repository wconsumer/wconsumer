<?php
namespace Drupal\wconsumer\Service\UnauthorizedResponseHandler;

use Guzzle\Common\Event;
use Guzzle\Common\Exception\RuntimeException;
use Guzzle\Http\Message\Response;



class Flickr extends Common {

  public static function getSubscribedEvents() {
    return array('request.complete' => 'onRequestComplete');
  }

  public function onRequestComplete(Event $event) {
    /** @var Response $response */
    $response = $event['response'];

    if ($response->isSuccessful()) {
      $errorCode = null;
      if (!isset($errorCode)) {
        $errorCode = $this->errorCodeFromJson($response);
      }
      if (!isset($errorCode)) {
        $errorCode = $this->errorCodeFromXml($response);
      }

      if ($errorCode == 98) {
        $this->fail();
      }
    }
  }

  private function errorCodeFromJson(Response $response) {
    try {
      $json = $response->json();
      if (@$json['stat'] === 'fail') {
        return @$json['code'];
      }
    }
    catch (RuntimeException $e) {
    }

    return null;
  }

  private function errorCodeFromXml(Response $response) {
    try {
      $xml = $response->xml();
      return (string)@$xml->{'err'}['code'];
    }
    catch (RuntimeException $e) {
    }

    return null;
  }
}
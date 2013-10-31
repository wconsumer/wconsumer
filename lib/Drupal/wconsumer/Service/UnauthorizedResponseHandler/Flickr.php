<?php
namespace Drupal\wconsumer\Service\UnauthorizedResponseHandler;

use Drupal\wconsumer\Service\Exception\NoUserCredentials;
use Guzzle\Common\Event;
use Guzzle\Common\Exception\RuntimeException;
use Guzzle\Http\Message\Response;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;



class Flickr implements EventSubscriberInterface {

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
        throw new NoUserCredentials();
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
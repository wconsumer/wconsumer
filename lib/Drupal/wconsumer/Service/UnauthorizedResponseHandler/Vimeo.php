<?php
namespace Drupal\wconsumer\Service\UnauthorizedResponseHandler;

use Drupal\wconsumer\Service\Exception\NoUserCredentials;
use Guzzle\Common\Event;
use Guzzle\Http\Message\Response;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;



class Vimeo implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return array('request.complete' => 'onRequestComplete');
  }

  public function onRequestComplete(Event $event) {
    /** @var Response $response */
    $response = $event['response'];

    if ($response->isSuccessful()) {
      $errorCode = null;
      switch ($response->getContentType()) {
        case 'application/json':
          $json = $response->json();
          $errorCode = @$json['err']['code'];
          break;
        case 'application/xml':
          $xml = $response->xml();
          $errorCode = (string)@$xml->{'err'}['code'];
          break;
      }

      if ($errorCode == 401) {
        throw new NoUserCredentials();
      }
    }
  }
}
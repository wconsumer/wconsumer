<?php
namespace Drupal\wconsumer\Service\UnauthorizedResponseHandler;

use Guzzle\Common\Event;
use Guzzle\Http\Message\Response;



class Vimeo extends Common {

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
        $this->fail();
      }
    }
  }
}
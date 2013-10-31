<?php
namespace Drupal\wconsumer\Service\UnauthorizedResponseHandler;

use Drupal\wconsumer\Service\Exception\NoUserCredentials;
use Guzzle\Common\Event;
use Guzzle\Http\Message\Response;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;



class Common implements EventSubscriberInterface {
  public static function getSubscribedEvents() {
    return array('request.exception' => 'onRequestException');
  }

  public function onRequestException(Event $event) {
    /** @var Response $response */
    $response = $event['response'];

    if ($response->getStatusCode() === 401) {
      throw new NoUserCredentials();
    }
  }
}
<?php
namespace Drupal\wconsumer\Service\UnauthorizedResponseHandler;

use Drupal\wconsumer\Service\Exception\NoUserCredentials;
use Drupal\wconsumer\Service\Service;
use Guzzle\Common\Event;
use Guzzle\Http\Message\Response;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;



class Common implements EventSubscriberInterface {

  private $service;
  private $userId;



  public function __construct(Service $service, $userId) {
    $this->service = $service;
    $this->userId = $userId;
  }

  public static function getSubscribedEvents() {
    return array('request.exception' => 'onRequestException');
  }

  public function onRequestException(Event $event) {
    /** @var Response $response */
    $response = $event['response'];

    if ($response->getStatusCode() === 401) {
      $this->fail();
    }
  }

  protected function fail() {
    $this->service->setCredentials(null, $this->userId);
    throw new NoUserCredentials($this->service);
  }
}
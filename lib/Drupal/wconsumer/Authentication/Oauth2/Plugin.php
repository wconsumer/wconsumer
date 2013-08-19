<?php
namespace Drupal\wconsumer\Authentication\Oauth2;

use Guzzle\Common\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\wconsumer\Authentication\Oauth2\AccessToken\BearerToken;



class Plugin implements EventSubscriberInterface {

  private $token;


  public function __construct($accessToken) {
    $this->token = new BearerToken($accessToken);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return array('request.before_send' => 'onRequestBeforeSend');
  }

  /**
   * Request before-send event handler
   *
   * @param Event $event Event received
   *
   * @return string
   */
  public function onRequestBeforeSend(Event $event) {
    /** @var \Guzzle\Http\Message\Request $request */
    $request = $event['request'];
    $request->setHeader('Authorization', $this->token->buildAuthorizationHeader());
  }
}

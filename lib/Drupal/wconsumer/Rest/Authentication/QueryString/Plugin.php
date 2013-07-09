<?php
namespace Drupal\wconsumer\Rest\Authentication\QueryString;

use Guzzle\Common\Event,
    Guzzle\Common\Collection,
    Symfony\Component\EventDispatcher\EventSubscriberInterface,
    Drupal\wconsumer\Rest\Authentication\Oauth2\AccessToken\TokenInterface,
    Drupal\wconsumer\Rest\Authentication\Oauth2\AccessToken\BearerToken,
    Drupal\wconsumer\Rest\Authentication\Oauth2\AccessToken\MacToken;

/**
 * QueryString Plugin
 *
 * Adds a query string variable to the request
 *
 * @package wconsumer
 * @subpackage querystring
 */
class Plugin implements EventSubscriberInterface 
{
    public function __construct($config)
    {
        $this->config = Collection::fromConfig($config, array(
            'query_key' => 'query_key',
            'query_value' => 'query_value',
        ), array(
            'query_key', 'query_value',
        ));
    }

   /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array('request.before_send' => 'onRequestBeforeSend');
    }

   /**
     * Request before-send event handler
     *
     * @param Event $event Event received
     * @return string
     */
    public function onRequestBeforeSend(Event $event)
    {
        $query = $event['request']->getQuery();
        $query->add($this->config['query_key'], $this->config['query_value']);
    }
}

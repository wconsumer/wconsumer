<?php
namespace Drupal\wconsumer\Authentication\QueryString;

use Guzzle\Common\Event;
use Guzzle\Common\Collection;
use Guzzle\Http\Message\Request;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


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
    private $config;



    public function __construct($config)
    {
        $this->config = Collection::fromConfig($config, array(), array('query_key', 'query_value'));
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
        /** @var Request $request */
        $request = $event['request'];
        $query = $request->getQuery();
        $query->add($this->config['query_key'], $this->config['query_value']);
    }
}
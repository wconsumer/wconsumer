<?php
namespace Drupal\wconsumer\Rest\Authentication\Oauth2;

use Guzzle\Common\Event,
    Guzzle\Common\Collection,
    Symfony\Component\EventDispatcher\EventSubscriberInterface,
    Drupal\wconsumer\Rest\Authentication\Oauth2\AccessToken\TokenInterface,
    Drupal\wconsumer\Rest\Authentication\Oauth2\AccessToken\BearerToken,
    Drupal\wconsumer\Rest\Authentication\Oauth2\AccessToken\MacToken;

/**
 * OAuth2Plugin
 *
 * This is written by <https://github.com/natmchugh/guzzle-oauth2-plugin>
 * Almost all credit is due to the other of the above mentioned oauth2 plugin
 */
class Plugin implements EventSubscriberInterface 
{

    public function __construct($config)
    {
        $this->config = Collection::fromConfig($config, array(
            'version' => '2.0',
            'consumer_key' => 'anonymous',
            'consumer_secret' => 'anonymous',
            'token_type' => 'Bearer',
        ), array(
            'consumer_key', 'consumer_secret', 'version', 'token_type',
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
        $request = $event['request'];

        if (is_string($this->config['access_token'])) {
            $params = array('access_token' => $this->config['access_token']);
            if (isset($this->config['token_format'])) {
                $params['token_format'] = $this->config['token_format'];
            }
            switch ($this->config['token_type']) {
                case 'Mac':
                    $this->config['access_token'] = new MacToken($params);
                    break;
                case 'Bearer':
                default:
                    $this->config['access_token'] = new BearerToken($params);
                    break;
            }
        }
        $request->setHeader(
            'Authorization',
            $this->buildAuthorizationHeader($this->config['access_token'])
        );

        return $this->config['access_token'];
    }

    /**
     * Builds the Authorization header for a request
     *
     * @param string $token the Oauth token
     *
     * @return string
     */
    private function buildAuthorizationHeader($token)
    {
        return (string) $token;
    }
}

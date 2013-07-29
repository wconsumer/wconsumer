<?php
  namespace Drupal\wconsumer\Tests\Authentication\QueryString;



  use Drupal\wconsumer\Rest\Authentication\QueryString\Plugin;
  use Guzzle\Common\Event;
  use Guzzle\Http\Message\Request;

  class PluginTest extends \PHPUnit_Framework_TestCase
  {
    /**
     * @expectedException \Guzzle\Common\Exception\InvalidArgumentException
     */
    public function testConstructionFailsIfRequiredValuesNotProvided()
    {
      $this->plugin(array());
    }

    public function testSubsribedEvents()
    {
      $plugin = $this->plugin();
      $this->assertSame(array('request.before_send' => 'onRequestBeforeSend'), $plugin->getSubscribedEvents());
    }

    public function testBeforeSendEventHandling()
    {
      $plugin = $this->plugin(array('query_key' => 'key', 'query_value' => 'topverysecret'));

      $request = new Request('get', '/test');
      $beforeSendEvent = new Event(array('request' => $request));

      $plugin->onRequestBeforeSend($beforeSendEvent);

      $this->assertSame('key=topverysecret', $request->getQuery(true));
    }

    private function plugin($config = null)
    {
      if (!isset($config))
      {
        $config = array('query_key' => 'password', 'query_value' => '12345');
      }

      $plugin = new Plugin($config);

      return $plugin;
    }
  }
?>
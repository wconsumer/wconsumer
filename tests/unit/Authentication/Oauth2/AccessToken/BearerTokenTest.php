<?php
  namespace Drupal\wconsumer\Tests\Unit\Authentication\Oauth2\AccessToken;

  use Drupal\wconsumer\Authentication\Oauth2\AccessToken\BearerToken;



  class BearerTokenTest extends \PHPUnit_Framework_TestCase
  {
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructionFailsOnEmptyAccessToken()
    {
      new BearerToken('');
    }

    public function testAuthHeaderBuilding()
    {
      $token = new BearerToken('abc');
      $this->assertSame('Bearer abc', $token->buildAuthorizationHeader());
    }
  }
?>
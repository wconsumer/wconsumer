<?php
namespace Drupal\wconsumer\AcceptanceTests;

use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\Wconsumer;
use Drupal\wconsumer\Service\Base as Service;



class OauthFlowTest extends SeleniumTestCase {

  public function setUpPage() {
    parent::setUp();

    $testUserName = @end(explode('\\', get_class($this))).'_user';

    $testUserNameEscaped = escapeshellarg($testUserName);
    $this->exec("drush user-cancel {$testUserNameEscaped} --delete-content --yes", TRUE);
    $this->exec("drush user-create {$testUserNameEscaped} --yes");
    $oneTimeLoginLink = $this->exec("drush user-login --browser=0 {$testUserNameEscaped} --yes");

    $this->url($oneTimeLoginLink);
    $this->byCssSelector('a[href^="/user/"][href$="/edit"]')->click();
  }

  /**
   * @dataProvider provideServices
   */
  public function testOauthFlow(Service $service) {
    $serviceNiceName = $service->getMeta()->niceName;

    $this->setupService($service);

    $this->clickVerticalServiceTab($service);
    $this->clickServiceButton("Allow access to my {$serviceNiceName} account");
    $this->expectExternalServicePage();
    $this->loginWithExternalService($service);
    $this->allowAccess();
    $this->expectDomesticPage();
    $this->expectSuccessMessage("your {$serviceNiceName} account is now linked with your local account");
    $this->clickVerticalServiceTab($service);
    $this->clickServiceButton("Revoke access to my {$serviceNiceName} account");
    $this->expectSuccessMessage("Your {$serviceNiceName} account has been revoked");
  }

  public static function provideServices() {
    return array (
      //array(Wconsumer::$github),
      //array(Wconsumer::$twitter),
      //array(Wconsumer::$linkedin),
      array(Wconsumer::$meetup),
    );
  }

  private function clickVerticalServiceTab(Service $service) {
    $this->find($this->using('xpath')->value(
      '//*[@id="edit-web-services"]'.
      '//*[contains(@class, "vertical-tab-button")]'.$this->xpathContains($service->getMeta()->niceName).
      '/a'
    ))->click();
  }

  private function clickServiceButton($buttonText) {
    $this->find($this->using('xpath')->value(
      '//*[contains(@class, "vertical-tabs-panes")]'.
      '//text()'.$this->xpathContains($buttonText).'/parent::*'
    ))->click();
  }

  private function expectExternalServicePage() {
    $currentDomain = parse_url($this->url(), PHP_URL_HOST);
    $domesticDomain = parse_url(DRUPAL_BASE_URL, PHP_URL_HOST);
    $this->assertNotSame($domesticDomain, $currentDomain);
  }

  private function loginWithExternalService(Service $service) {
    $credentials = Credentials::fromArray($this->keys->get($service->getName(), 'webuser'));

    $knownLoginFields = array(
      '#login_field', // github
      '#username_or_email', // twitter
      '#session_key-oauthAuthorizeForm', // linkedin
      '#email', // meetup
    );

    $knownPasswordFields = array(
      '#password', // github, twitter, meetup
      '#session_password-oauthAuthorizeForm', // linkedin
    );

    $login = $this->byCssSelector(join(', ', $knownLoginFields));
    $password = $this->byCssSelector(join(', ', $knownPasswordFields));

    $login->value($credentials->token);
    $password->value($credentials->secret);

    $login->submit();
  }

  private function allowAccess() {
    if ($allowAccessButton = $this->elementExists($this->using('xpath')->value('//button[contains(., "Allow access")]'))) {
      $allowAccessButton->click();
    }
  }

  private function expectDomesticPage() {
    $currentDomain = parse_url($this->url(), PHP_URL_HOST);
    $domesticDomain = parse_url(DRUPAL_BASE_URL, PHP_URL_HOST);
    $this->assertSame($domesticDomain, $currentDomain);
  }

  public function expectSuccessMessage($message, $waitUntil = TRUE) {
    if ($waitUntil) {
      $self = $this;
      return $this->waitUntil(
        function() use($self, $message) {
          return $self->expectSuccessMessage($message, FALSE);
        },
        5000
      );
    }
    else {
      return $this->find($this->using('xpath')->value(
        '//*[contains(@class, "messages")][contains(@class, "status")]'.
        $this->xpathContains($message)
      ));
    }
  }

  private function find(\PHPUnit_Extensions_Selenium2TestCase_ElementCriteria $criteria) {
    $element = $this->element($criteria);
    $this->assertTrue($element->displayed());
    return $element;
  }

  private function setupService(Service $service) {
    $service->setEnabled(TRUE);
    $service->setCredentials(Credentials::fromArray($this->keys->get($service->getName(), 'app')));
    $this->refresh();
  }

  private function elementExists(\PHPUnit_Extensions_Selenium2TestCase_ElementCriteria $criteria) {
    $element = null;
    try {
      $element = $this->find($criteria);
    }
    catch (\PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {
      if ($e->getCode() === \PHPUnit_Extensions_Selenium2TestCase_WebDriverException::NoSuchElement) {
        return false;
      }

      throw $e;
    }

    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element $element */
    return $element;
  }

  private function xpathContains($text) {
    return
      '['.
        'contains('.
          'translate(., "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), '.
          '"'.strtolower($text).'"'.
        ')'.
      ']';
  }

  private function exec($command, $ignoreErrors = false) {
    $output = null;
    $result = null;
    exec("{$command} 2>&1", $output, $result);
    $output = join("\n", $output);

    if ($result != 0 && !$ignoreErrors) {
      throw new \RuntimeException("Command '{$command}' exited with code {$result}. Output: '{$output}'.");
    }

    return $output;
  }
}
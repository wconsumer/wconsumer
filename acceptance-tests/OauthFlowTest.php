<?php
namespace Drupal\wconsumer\AcceptanceTests;

use Drupal\wconsumer\Authentication\Credentials;
use Drupal\wconsumer\Service\Dropbox;
use Drupal\wconsumer\Service\Google;
use Drupal\wconsumer\Service\Vimeo;
use Drupal\wconsumer\Wconsumer;
use Drupal\wconsumer\Service\Service;



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

    $this->clickServiceTab($service);
    $this->clickServiceButton("Allow access to my {$serviceNiceName} account");
    $this->expectExternalDomain();
    $this->loginWithExternalService($service);
    $this->allowAccess();
    $this->expectHomeDomain();
    $this->expectSuccessMessage("your {$serviceNiceName} account is now linked with your local account");
    $this->clickServiceTab($service);
    $this->clickServiceButton("Revoke access to my {$serviceNiceName} account");
    $this->expectSuccessMessage("Your {$serviceNiceName} account has been revoked");
  }

  public static function provideServices() {
    $result = array();

    foreach (Wconsumer::instance()->services as $service) {
      if ($service instanceof Dropbox || // requires https, need to think more on a way to test it
          $service instanceof Google) { // non-public domains not allowed in redirect uri. can't test it.
        continue;
      }

      $result[] = array($service);
    }

    return $result;
  }

  private function clickServiceTab(Service $service) {
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

  private function expectExternalDomain() {
    $this->assertNotSame($this->homeDomain(), $this->currentDomain());
  }

  private function expectHomeDomain() {
    $this->assertSame($this->homeDomain(), $this->currentDomain());
  }

  private function loginWithExternalService(Service $service) {
    $credentials = Credentials::fromArray($this->keys->get($service->getName(), 'webuser'));

    $knownLoginFields = array(
      '#login_field', // github
      '#username_or_email', // twitter
      '#session_key-oauthAuthorizeForm', // linkedin oauth
      '#session_key-oauth2SAuthorizeForm', // linkedin oauth2
      '#email', // meetup, vimeo, facebook
      '#username', // flickr
    );

    $knownPasswordFields = array(
      '#password', // github, twitter, meetup, vimeo
      '#session_password-oauthAuthorizeForm', // linkedin oauth
      '#session_password-oauth2SAuthorizeForm', // linkedin oauth2
      '#pass', // facebook
      '#passwd', // flickr
    );

    $login = $this->byCssSelector(join(', ', $knownLoginFields));
    $password = $this->byCssSelector(join(', ', $knownPasswordFields));

    $login->value($credentials->token);
    $password->value($credentials->secret);

    if ($service instanceof Vimeo) {
      $submit = $this->byCssSelector('input[type="submit"][value="Log In"]');
      $submit->click();
    }
    else {
      $login->submit();
    }
  }

  private function allowAccess() {
    if ($this->homeDomain() === $this->currentDomain()) {
      return;
    }

    $knownAllowAccessButtons = array(
      'github'    => $this->using('xpath')->value('//button[contains(., "Allow access")]'),
      'vimeo'     => $this->using('css selector')->value('*[name="accept"][value="Allow"]'),
      'facebook'  => $this->using('css selector')->value('*[type="submit"][name="__CONFIRM__"]'),
      'flickr'    => $this->using('css selector')->value('*[type="submit"][value="OK, I\'LL AUTHORIZE IT"]'),
    );

    foreach ($knownAllowAccessButtons as $selector) {
      if ($allowAccessButton = $this->elementExists($selector)) {
        $allowAccessButton->click();
        return;
      }
    }

    $this->fail('Confirm button not found');
  }

  public function expectSuccessMessage($message, $waitUntil = TRUE) {
    if ($waitUntil) {
      $self = $this;
      return $this->waitUntil(
        function() use($self, $message) {
          return $self->expectSuccessMessage($message, FALSE);
        },
        10000
      );
    }
    else {
      return $this->find($this->using('xpath')->value(
        '//*[contains(@class, "messages")][contains(@class, "status")]'.
        $this->xpathContains($message)
      ));
    }
  }

  private function homeDomain() {
    return parse_url($this->url(), PHP_URL_HOST);
  }

  private function currentDomain() {
    return parse_url(DRUPAL_BASE_URL, PHP_URL_HOST);
  }

  private function find(\PHPUnit_Extensions_Selenium2TestCase_ElementCriteria $criteria) {
    $element = $this->element($criteria);
    $this->assertTrue($element->displayed());
    return $element;
  }

  private function setupService(Service $service) {
    $service->setEnabled(TRUE);
    $service->setServiceCredentials(Credentials::fromArray($this->keys->get($service->getName(), 'app')));
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
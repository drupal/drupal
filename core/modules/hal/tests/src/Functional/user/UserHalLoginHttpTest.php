<?php

namespace Drupal\Tests\hal\Functional\user;

use Drupal\Tests\user\Functional\UserLoginHttpTest;
use GuzzleHttp\Cookie\CookieJar;
use Drupal\hal\Encoder\JsonEncoder as HALJsonEncoder;
use Symfony\Component\Serializer\Serializer;

/**
 * Tests login and password reset via direct HTTP in hal_json format.
 *
 * @group hal
 * @group legacy
 */
class UserHalLoginHttpTest extends UserLoginHttpTest {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['hal', 'dblog'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->cookies = new CookieJar();
    $encoders = [new HALJsonEncoder()];
    $this->serializer = new Serializer([], $encoders);
  }

  /**
   * {@inheritdoc}
   */
  public function testLogin() {
    $this->doTestLogin('hal_json');
  }

  /**
   * {@inheritdoc}
   */
  public function testPasswordReset() {
    // Create a user account.
    $account = $this->drupalCreateUser();

    $this->doTestPasswordReset('hal_json', $account);
    $this->doTestGlobalLoginFloodControl('hal_json');
    $this->doTestPerUserLoginFloodControl('hal_json');
    $this->doTestLogoutCsrfProtection('hal_json');
  }

}

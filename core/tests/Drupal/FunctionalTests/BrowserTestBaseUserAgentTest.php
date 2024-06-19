<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests BrowserTestBase functionality.
 *
 * @group browsertestbase
 */
class BrowserTestBaseUserAgentTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The user agent string to use.
   *
   * @var string
   */
  protected $agent;

  /**
   * Tests validation of the User-Agent header we use to perform test requests.
   */
  public function testUserAgentValidation(): void {
    $assert_session = $this->assertSession();
    $system_path = $this->buildUrl(\Drupal::service('extension.list.module')->getPath('system'));
    $http_path = $system_path . '/tests/http.php/user/login';
    $https_path = $system_path . '/tests/https.php/user/login';
    // Generate a valid test User-Agent to pass validation.
    $this->assertNotFalse(preg_match('/test\d+/', $this->databasePrefix, $matches), 'Database prefix contains test prefix.');
    $this->agent = drupal_generate_test_ua($matches[0]);

    // Test pages only available for testing.
    $this->drupalGet($http_path);
    $assert_session->statusCodeEquals(200);
    $this->drupalGet($https_path);
    $assert_session->statusCodeEquals(200);

    // Now slightly modify the HMAC on the header, which should not validate.
    $this->agent = 'X';
    $this->drupalGet($http_path);
    $assert_session->statusCodeEquals(403);
    $this->drupalGet($https_path);
    $assert_session->statusCodeEquals(403);

    // Use a real User-Agent and verify that the special files http.php and
    // https.php can't be accessed.
    $this->agent = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';
    $this->drupalGet($http_path);
    $assert_session->statusCodeEquals(403);
    $this->drupalGet($https_path);
    $assert_session->statusCodeEquals(403);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareRequest() {
    $session = $this->getSession();
    if ($this->agent) {
      $session->setCookie('SIMPLETEST_USER_AGENT', $this->agent);
    }
    else {
      $session->setCookie('SIMPLETEST_USER_AGENT', drupal_generate_test_ua($this->databasePrefix));
    }
  }

}

<?php

/**
 * @file
 * Contains \Drupal\basic_auth\Tests\Authentication\BasicAuthTest.
 */

namespace Drupal\basic_auth\Tests\Authentication;

use Drupal\Core\Authentication\Provider\BasicAuth;
use Drupal\simpletest\WebTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test for http basic authentication.
 */
class BasicAuthTest extends WebTestBase {

  /**
   * Modules enabled for all tests.
   *
   * @var array
   */
  public static $modules = array('basic_auth', 'router_test');

  public static function getInfo() {
    return array(
      'name' => 'BasicAuth authentication',
      'description' => 'Tests for BasicAuth authentication provider.',
      'group' => 'Authentication',
    );
  }

  /**
   * Test http basic authentication.
   */
  public function testBasicAuth() {
    $account = $this->drupalCreateUser();

    $this->basicAuthGet('router_test/test11', $account->getUsername(), $account->pass_raw);
    $this->assertText($account->getUsername(), 'Account name is displayed.');
    $this->assertResponse('200', 'HTTP response is OK');
    $this->curlClose();

    $this->basicAuthGet('router_test/test11', $account->getUsername(), $this->randomName());
    $this->assertNoText($account->getUsername(), 'Bad basic auth credentials do not authenticate the user.');
    $this->assertResponse('403', 'Access is not granted.');
    $this->curlClose();

    $this->drupalGet('router_test/test11');
    $this->assertResponse('401', 'Not authenticated on the route that allows only basic_auth. Prompt to authenticate received.');

    $this->drupalGet('admin');
    $this->assertResponse('403', 'No authentication prompt for routes not explicitly defining authentication providers.');

    $account = $this->drupalCreateUser(array('access administration pages'));

    $this->basicAuthGet('admin', $account->getUsername(), $account->pass_raw);
    $this->assertNoLink('Log out', 0, 'User is not logged in');
    $this->assertResponse('403', 'No basic authentication for routes not explicitly defining authentication providers.');
    $this->curlClose();
  }

  /**
   * Does HTTP basic auth request.
   *
   * We do not use \Drupal\simpletest\WebTestBase::drupalGet because we need to
   * set curl settings for basic authentication.
   *
   * @param string $path
   *   The request path.
   * @param string $username
   *   The user name to authenticate with.
   * @param string $password
   *   The password.
   *
   * @return string
   *   Curl output.
   */
  protected function basicAuthGet($path, $username, $password) {
    $out = $this->curlExec(
      array(
        CURLOPT_HTTPGET => TRUE,
        CURLOPT_URL => url($path, array('absolute' => TRUE)),
        CURLOPT_NOBODY => FALSE,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => $username . ':' . $password,
      )
    );

    $this->verbose('GET request to: ' . $path .
      '<hr />' . $out);

    return $out;
  }
}

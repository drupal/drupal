<?php

namespace Drupal\Tests\user\Functional;

use Drupal\Core\Flood\DatabaseBackend;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Controller\UserAuthenticationController;
use GuzzleHttp\Cookie\CookieJar;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Drupal\hal\Encoder\JsonEncoder as HALJsonEncoder;
use Symfony\Component\Serializer\Serializer;

/**
 * Tests login via direct HTTP.
 *
 * @group user
 */
class UserLoginHttpTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['hal'];

  /**
   * The cookie jar.
   *
   * @var \GuzzleHttp\Cookie\CookieJar
   */
  protected $cookies;

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->cookies = new CookieJar();
    $encoders = [new JsonEncoder(), new XmlEncoder(), new HALJsonEncoder()];
    $this->serializer = new Serializer([], $encoders);
  }

  /**
   * Executes a login HTTP request.
   *
   * @param string $name
   *   The username.
   * @param string $pass
   *   The user password.
   * @param string $format
   *   The format to use to make the request.
   *
   * @return \Psr\Http\Message\ResponseInterface The HTTP response.
   *   The HTTP response.
   */
  protected function loginRequest($name, $pass, $format = 'json') {
    $user_login_url = Url::fromRoute('user.login.http')
      ->setRouteParameter('_format', $format)
      ->setAbsolute();

    $request_body = [];
    if (isset($name)) {
      $request_body['name'] = $name;
    }
    if (isset($pass)) {
      $request_body['pass'] = $pass;
    }

    $result = \Drupal::httpClient()->post($user_login_url->toString(), [
      'body' => $this->serializer->encode($request_body, $format),
      'headers' => [
        'Accept' => "application/$format",
      ],
      'http_errors' => FALSE,
      'cookies' => $this->cookies,
    ]);
    return $result;
  }

  /**
   * Tests user session life cycle.
   */
  public function testLogin() {
    $client = \Drupal::httpClient();
    foreach ([FALSE, TRUE] as $serialization_enabled_option) {
      if ($serialization_enabled_option) {
        /** @var \Drupal\Core\Extension\ModuleInstaller $module_installer */
        $module_installer = $this->container->get('module_installer');
        $module_installer->install(['serialization']);
        $formats = ['json', 'xml', 'hal_json'];
      }
      else {
        // Without the serialization module only JSON is supported.
        $formats = ['json'];
      }
      foreach ($formats as $format) {
        // Create new user for each iteration to reset flood.
        // Grant the user administer users permissions to they can see the
        // 'roles' field.
        $account = $this->drupalCreateUser(['administer users']);
        $name = $account->getUsername();
        $pass = $account->passRaw;

        $login_status_url = $this->getLoginStatusUrlString($format);
        $response = $client->get($login_status_url);
        $this->assertHttpResponse($response, 200, UserAuthenticationController::LOGGED_OUT);

        // Flooded.
        $this->config('user.flood')
          ->set('user_limit', 3)
          ->save();

        $response = $this->loginRequest($name, 'wrong-pass', $format);
        $this->assertHttpResponseWithMessage($response, 400, 'Sorry, unrecognized username or password.', $format);

        $response = $this->loginRequest($name, 'wrong-pass', $format);
        $this->assertHttpResponseWithMessage($response, 400, 'Sorry, unrecognized username or password.', $format);

        $response = $this->loginRequest($name, 'wrong-pass', $format);
        $this->assertHttpResponseWithMessage($response, 400, 'Sorry, unrecognized username or password.', $format);

        $response = $this->loginRequest($name, 'wrong-pass', $format);
        $this->assertHttpResponseWithMessage($response, 403, 'Too many failed login attempts from your IP address. This IP address is temporarily blocked.', $format);

        // After testing the flood control we can increase the limit.
        $this->config('user.flood')
          ->set('user_limit', 100)
          ->save();

        $response = $this->loginRequest(NULL, NULL, $format);
        $this->assertHttpResponseWithMessage($response, 400, 'Missing credentials.', $format);

        $response = $this->loginRequest(NULL, $pass, $format);
        $this->assertHttpResponseWithMessage($response, 400, 'Missing credentials.name.', $format);

        $response = $this->loginRequest($name, NULL, $format);
        $this->assertHttpResponseWithMessage($response, 400, 'Missing credentials.pass.', $format);

        // Blocked.
        $account
          ->block()
          ->save();

        $response = $this->loginRequest($name, $pass, $format);
        $this->assertHttpResponseWithMessage($response, 400, 'The user has not been activated or is blocked.', $format);

        $account
          ->activate()
          ->save();

        $response = $this->loginRequest($name, 'garbage', $format);
        $this->assertHttpResponseWithMessage($response, 400, 'Sorry, unrecognized username or password.', $format);

        $response = $this->loginRequest('garbage', $pass, $format);
        $this->assertHttpResponseWithMessage($response, 400, 'Sorry, unrecognized username or password.', $format);

        $response = $this->loginRequest($name, $pass, $format);
        $this->assertEquals(200, $response->getStatusCode());
        $result_data = $this->serializer->decode($response->getBody(), $format);
        $this->assertEquals($name, $result_data['current_user']['name']);
        $this->assertEquals($account->id(), $result_data['current_user']['uid']);
        $this->assertEquals($account->getRoles(), $result_data['current_user']['roles']);
        $logout_token = $result_data['logout_token'];

        $response = $client->get($login_status_url, ['cookies' => $this->cookies]);
        $this->assertHttpResponse($response, 200, UserAuthenticationController::LOGGED_IN);

        $response = $this->logoutRequest($format, $logout_token);
        $this->assertEquals(204, $response->getStatusCode());

        $response = $client->get($login_status_url, ['cookies' => $this->cookies]);
        $this->assertHttpResponse($response, 200, UserAuthenticationController::LOGGED_OUT);

        $this->resetFlood();
      }
    }
  }

  /**
   * Gets a value for a given key from the response.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response object.
   * @param string $key
   *   The key for the value.
   * @param string $format
   *   The encoded format.
   *
   * @return mixed
   *   The value for the key.
   */
  protected function getResultValue(ResponseInterface $response, $key, $format) {
    $decoded = $this->serializer->decode((string) $response->getBody(), $format);
    if (is_array($decoded)) {
      return $decoded[$key];
    }
    else {
      return $decoded->{$key};
    }
  }

  /**
   * Resets all flood entries.
   */
  protected function resetFlood() {
    $this->container->get('database')->delete(DatabaseBackend::TABLE_NAME)->execute();
  }

  /**
   * Tests the global login flood control.
   *
   * @see \Drupal\basic_auth\Tests\Authentication\BasicAuthTest::testGlobalLoginFloodControl
   * @see \Drupal\user\Tests\UserLoginTest::testGlobalLoginFloodControl
   */
  public function testGlobalLoginFloodControl() {
    $this->config('user.flood')
      ->set('ip_limit', 2)
      // Set a high per-user limit out so that it is not relevant in the test.
      ->set('user_limit', 4000)
      ->save();

    $user = $this->drupalCreateUser([]);
    $incorrect_user = clone $user;
    $incorrect_user->passRaw .= 'incorrect';

    // Try 2 failed logins.
    for ($i = 0; $i < 2; $i++) {
      $response = $this->loginRequest($incorrect_user->getUsername(), $incorrect_user->passRaw);
      $this->assertEquals('400', $response->getStatusCode());
    }

    // IP limit has reached to its limit. Even valid user credentials will fail.
    $response = $this->loginRequest($user->getUsername(), $user->passRaw);
    $this->assertHttpResponseWithMessage($response, '403', 'Access is blocked because of IP based flood prevention.');
  }

  /**
   * Checks a response for status code and body.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response object.
   * @param int $expected_code
   *   The expected status code.
   * @param mixed $expected_body
   *   The expected response body.
   */
  protected function assertHttpResponse(ResponseInterface $response, $expected_code, $expected_body) {
    $this->assertEquals($expected_code, $response->getStatusCode());
    $this->assertEquals($expected_body, (string) $response->getBody());
  }

  /**
   * Checks a response for status code and message.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response object.
   * @param int $expected_code
   *   The expected status code.
   * @param string $expected_message
   *   The expected message encoded in response.
   * @param string $format
   *   The format that the response is encoded in.
   */
  protected function assertHttpResponseWithMessage(ResponseInterface $response, $expected_code, $expected_message, $format = 'json') {
    $this->assertEquals($expected_code, $response->getStatusCode());
    $this->assertEquals($expected_message, $this->getResultValue($response, 'message', $format));
  }

  /**
   * Test the per-user login flood control.
   *
   * @see \Drupal\user\Tests\UserLoginTest::testPerUserLoginFloodControl
   * @see \Drupal\basic_auth\Tests\Authentication\BasicAuthTest::testPerUserLoginFloodControl
   */
  public function testPerUserLoginFloodControl() {
    foreach ([TRUE, FALSE] as $uid_only_setting) {
      $this->config('user.flood')
        // Set a high global limit out so that it is not relevant in the test.
        ->set('ip_limit', 4000)
        ->set('user_limit', 3)
        ->set('uid_only', $uid_only_setting)
        ->save();

      $user1 = $this->drupalCreateUser([]);
      $incorrect_user1 = clone $user1;
      $incorrect_user1->passRaw .= 'incorrect';

      $user2 = $this->drupalCreateUser([]);

      // Try 2 failed logins.
      for ($i = 0; $i < 2; $i++) {
        $response = $this->loginRequest($incorrect_user1->getUsername(), $incorrect_user1->passRaw);
        $this->assertHttpResponseWithMessage($response, 400, 'Sorry, unrecognized username or password.');
      }

      // A successful login will reset the per-user flood control count.
      $response = $this->loginRequest($user1->getUsername(), $user1->passRaw);
      $result_data = $this->serializer->decode($response->getBody(), 'json');
      $this->logoutRequest('json', $result_data['logout_token']);

      // Try 3 failed logins for user 1, they will not trigger flood control.
      for ($i = 0; $i < 3; $i++) {
        $response = $this->loginRequest($incorrect_user1->getUsername(), $incorrect_user1->passRaw);
        $this->assertHttpResponseWithMessage($response, 400, 'Sorry, unrecognized username or password.');
      }

      // Try one successful attempt for user 2, it should not trigger any
      // flood control.
      $this->drupalLogin($user2);
      $this->drupalLogout();

      // Try one more attempt for user 1, it should be rejected, even if the
      // correct password has been used.
      $response = $this->loginRequest($user1->getUsername(), $user1->passRaw);
      // Depending on the uid_only setting the error message will be different.
      if ($uid_only_setting) {
        $excepted_message = 'There have been more than 3 failed login attempts for this account. It is temporarily blocked. Try again later or request a new password.';
      }
      else {
        $excepted_message = 'Too many failed login attempts from your IP address. This IP address is temporarily blocked.';
      }
      $this->assertHttpResponseWithMessage($response, 403, $excepted_message);
    }

  }

  /**
   * Executes a logout HTTP request.
   *
   * @param string $format
   *   The format to use to make the request.
   * @param string $logout_token
   *   The csrf token for user logout.
   *
   * @return \Psr\Http\Message\ResponseInterface The HTTP response.
   *   The HTTP response.
   */
  protected function logoutRequest($format = 'json', $logout_token = '') {
    /** @var \GuzzleHttp\Client $client */
    $client = $this->container->get('http_client');
    $user_logout_url = Url::fromRoute('user.logout.http')
      ->setRouteParameter('_format', $format)
      ->setAbsolute();
    if ($logout_token) {
      $user_logout_url->setOption('query', ['token' => $logout_token]);
    }
    $post_options = [
      'headers' => [
        'Accept' => "application/$format",
      ],
      'http_errors' => FALSE,
      'cookies' => $this->cookies,
    ];

    $response = $client->post($user_logout_url->toString(), $post_options);
    return $response;
  }

  /**
   * Test csrf protection of User Logout route.
   */
  public function testLogoutCsrfProtection() {
    $client = \Drupal::httpClient();
    $login_status_url = $this->getLoginStatusUrlString();
    $account = $this->drupalCreateUser();
    $name = $account->getUsername();
    $pass = $account->passRaw;

    $response = $this->loginRequest($name, $pass);
    $this->assertEquals(200, $response->getStatusCode());
    $result_data = $this->serializer->decode($response->getBody(), 'json');

    $logout_token = $result_data['logout_token'];

    // Test third party site posting to current site with logout request.
    // This should not logout the current user because it lacks the CSRF
    // token.
    $response = $this->logoutRequest('json');
    $this->assertEquals(403, $response->getStatusCode());

    // Ensure still logged in.
    $response = $client->get($login_status_url, ['cookies' => $this->cookies]);
    $this->assertHttpResponse($response, 200, UserAuthenticationController::LOGGED_IN);

    // Try with an incorrect token.
    $response = $this->logoutRequest('json', 'not-the-correct-token');
    $this->assertEquals(403, $response->getStatusCode());

    // Ensure still logged in.
    $response = $client->get($login_status_url, ['cookies' => $this->cookies]);
    $this->assertHttpResponse($response, 200, UserAuthenticationController::LOGGED_IN);

    // Try a logout request with correct token.
    $response = $this->logoutRequest('json', $logout_token);
    $this->assertEquals(204, $response->getStatusCode());

    // Ensure actually logged out.
    $response = $client->get($login_status_url, ['cookies' => $this->cookies]);
    $this->assertHttpResponse($response, 200, UserAuthenticationController::LOGGED_OUT);
  }

  /**
   * Gets the URL string for checking login.
   *
   * @param string $format
   *   The format to use to make the request.
   *
   * @return string
   *   The URL string.
   */
  protected function getLoginStatusUrlString($format = 'json') {
    $user_login_status_url = Url::fromRoute('user.login_status.http');
    $user_login_status_url->setRouteParameter('_format', $format);
    $user_login_status_url->setAbsolute();
    return $user_login_status_url->toString();
  }

}

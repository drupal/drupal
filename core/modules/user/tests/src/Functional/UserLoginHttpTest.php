<?php

namespace Drupal\Tests\user\Functional;

use Drupal\Core\Flood\DatabaseBackend;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Controller\UserAuthenticationController;
use GuzzleHttp\Cookie\CookieJar;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Serializer;

/**
 * Tests login and password reset via direct HTTP.
 *
 * @group user
 */
class UserLoginHttpTest extends BrowserTestBase {

  use AssertMailTrait {
    getMails as drupalGetMails;
  }

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['dblog'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
  protected function setUp(): void {
    parent::setUp();
    $this->cookies = new CookieJar();
    $encoders = [new JsonEncoder(), new XmlEncoder()];
    $this->serializer = new Serializer([], $encoders);
  }

  /**
   * Executes a login HTTP request for a given serialization format.
   *
   * @param string $name
   *   The username.
   * @param string $pass
   *   The user password.
   * @param string $format
   *   The format to use to make the request.
   *
   * @return \Psr\Http\Message\ResponseInterface
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
    // Without the serialization module only JSON is supported.
    $this->doTestLogin('json');

    // Enable serialization so we have access to additional formats.
    $this->container->get('module_installer')->install(['serialization']);
    $this->rebuildAll();

    $this->doTestLogin('json');
    $this->doTestLogin('xml');
  }

  /**
   * Do login testing for a given serialization format.
   *
   * @param string $format
   *   Serialization format.
   */
  protected function doTestLogin($format) {
    $client = \Drupal::httpClient();
    // Create new user for each iteration to reset flood.
    // Grant the user administer users permissions to they can see the
    // 'roles' field.
    $account = $this->drupalCreateUser(['administer users']);
    $name = $account->getAccountName();
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

    // Logging in while already logged in results in a 403 with helpful message.
    $response = $this->loginRequest($name, $pass, $format);
    $this->assertSame(403, $response->getStatusCode());
    $this->assertSame(['message' => 'This route can only be accessed by anonymous users.'], $this->serializer->decode($response->getBody(), $format));

    $response = $client->get($login_status_url, ['cookies' => $this->cookies]);
    $this->assertHttpResponse($response, 200, UserAuthenticationController::LOGGED_IN);

    $response = $this->logoutRequest($format, $logout_token);
    $this->assertEquals(204, $response->getStatusCode());

    $response = $client->get($login_status_url, ['cookies' => $this->cookies]);
    $this->assertHttpResponse($response, 200, UserAuthenticationController::LOGGED_OUT);

    $this->resetFlood();
  }

  /**
   * Executes a password HTTP request for a given serialization format.
   *
   * @param array $request_body
   *   The request body.
   * @param string $format
   *   The format to use to make the request.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response.
   */
  protected function passwordRequest(array $request_body, $format = 'json') {
    $password_reset_url = Url::fromRoute('user.pass.http')
      ->setRouteParameter('_format', $format)
      ->setAbsolute();

    $result = \Drupal::httpClient()->post($password_reset_url->toString(), [
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
   * Tests user password reset.
   */
  public function testPasswordReset() {
    // Create a user account.
    $account = $this->drupalCreateUser();

    // Without the serialization module only JSON is supported.
    $this->doTestPasswordReset('json', $account);

    // Enable serialization so we have access to additional formats.
    $this->container->get('module_installer')->install(['serialization']);
    $this->rebuildAll();

    $this->doTestPasswordReset('json', $account);
    $this->doTestPasswordReset('xml', $account);

    $this->doTestGlobalLoginFloodControl('json');
    $this->doTestPerUserLoginFloodControl('json');
    $this->doTestLogoutCsrfProtection('json');
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
   * Tests the global login flood control for a given serialization format.
   *
   * @param string $format
   *   The encoded format.
   *
   * @see \Drupal\basic_auth\Authentication\Provider\BasicAuthTest::testGlobalLoginFloodControl
   * @see \Drupal\Tests\user\Functional\UserLoginTest::testGlobalLoginFloodControl
   */
  public function doTestGlobalLoginFloodControl(string $format): void {
    $database = \Drupal::database();
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
      $response = $this->loginRequest($incorrect_user->getAccountName(), $incorrect_user->passRaw, $format);
      $this->assertEquals('400', $response->getStatusCode());
    }

    // IP limit has reached to its limit. Even valid user credentials will fail.
    $response = $this->loginRequest($user->getAccountName(), $user->passRaw, $format);
    $this->assertHttpResponseWithMessage($response, '403', 'Access is blocked because of IP based flood prevention.', $format);
    $last_log = $database->select('watchdog', 'w')
      ->fields('w', ['message'])
      ->condition('type', 'user')
      ->orderBy('wid', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchField();
    $this->assertEquals('Flood control blocked login attempt from %ip', $last_log, 'A watchdog message was logged for the login attempt blocked by flood control per IP.');
  }

  /**
   * Checks a response for status code and body.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response object.
   * @param int $expected_code
   *   The expected status code.
   * @param string $expected_body
   *   The expected response body.
   *
   * @internal
   */
  protected function assertHttpResponse(ResponseInterface $response, int $expected_code, string $expected_body): void {
    $this->assertEquals($expected_code, $response->getStatusCode());
    $this->assertEquals($expected_body, $response->getBody());
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
   *
   * @internal
   */
  protected function assertHttpResponseWithMessage(ResponseInterface $response, int $expected_code, string $expected_message, string $format = 'json'): void {
    $this->assertEquals($expected_code, $response->getStatusCode());
    $this->assertEquals($expected_message, $this->getResultValue($response, 'message', $format));
  }

  /**
   * Tests the per-user login flood control for a given serialization format.
   *
   * @see \Drupal\basic_auth\Authentication\Provider\BasicAuthTest::testPerUserLoginFloodControl
   * @see \Drupal\Tests\user\Functional\UserLoginTest::testPerUserLoginFloodControl
   */
  public function doTestPerUserLoginFloodControl($format): void {
    $database = \Drupal::database();
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
        $response = $this->loginRequest($incorrect_user1->getAccountName(), $incorrect_user1->passRaw, $format);
        $this->assertHttpResponseWithMessage($response, 400, 'Sorry, unrecognized username or password.', $format);
      }

      // A successful login will reset the per-user flood control count.
      $response = $this->loginRequest($user1->getAccountName(), $user1->passRaw, $format);
      $result_data = $this->serializer->decode($response->getBody(), $format);
      $this->logoutRequest($format, $result_data['logout_token']);

      // Try 3 failed logins for user 1, they will not trigger flood control.
      for ($i = 0; $i < 3; $i++) {
        $response = $this->loginRequest($incorrect_user1->getAccountName(), $incorrect_user1->passRaw, $format);
        $this->assertHttpResponseWithMessage($response, 400, 'Sorry, unrecognized username or password.', $format);
      }

      // Try one successful attempt for user 2, it should not trigger any
      // flood control.
      $this->drupalLogin($user2);
      $this->drupalLogout();

      // Try one more attempt for user 1, it should be rejected, even if the
      // correct password has been used.
      $response = $this->loginRequest($user1->getAccountName(), $user1->passRaw, $format);
      // Depending on the uid_only setting the error message will be different.
      if ($uid_only_setting) {
        $expected_message = 'There have been more than 3 failed login attempts for this account. It is temporarily blocked. Try again later or request a new password.';
        $expected_log = 'Flood control blocked login attempt for uid %uid';
      }
      else {
        $expected_message = 'Too many failed login attempts from your IP address. This IP address is temporarily blocked.';
        $expected_log = 'Flood control blocked login attempt for uid %uid from %ip';
      }
      $this->assertHttpResponseWithMessage($response, 403, $expected_message, $format);
      $last_log = $database->select('watchdog', 'w')
        ->fields('w', ['message'])
        ->condition('type', 'user')
        ->orderBy('wid', 'DESC')
        ->range(0, 1)
        ->execute()
        ->fetchField();
      $this->assertEquals($expected_log, $last_log, 'A watchdog message was logged for the login attempt blocked by flood control per user.');
    }

  }

  /**
   * Executes a logout HTTP request for a given serialization format.
   *
   * @param string $format
   *   The format to use to make the request.
   * @param string $logout_token
   *   The csrf token for user logout.
   *
   * @return \Psr\Http\Message\ResponseInterface
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
   * Tests csrf protection of User Logout route for given serialization format.
   */
  public function doTestLogoutCsrfProtection(string $format): void {
    $client = \Drupal::httpClient();
    $login_status_url = $this->getLoginStatusUrlString();
    $account = $this->drupalCreateUser();
    $name = $account->getAccountName();
    $pass = $account->passRaw;

    $response = $this->loginRequest($name, $pass, $format);
    $this->assertEquals(200, $response->getStatusCode());
    $result_data = $this->serializer->decode($response->getBody(), $format);

    $logout_token = $result_data['logout_token'];

    // Test third party site posting to current site with logout request.
    // This should not logout the current user because it lacks the CSRF
    // token.
    $response = $this->logoutRequest($format);
    $this->assertEquals(403, $response->getStatusCode());

    // Ensure still logged in.
    $response = $client->get($login_status_url, ['cookies' => $this->cookies]);
    $this->assertHttpResponse($response, 200, UserAuthenticationController::LOGGED_IN);

    // Try with an incorrect token.
    $response = $this->logoutRequest($format, 'not-the-correct-token');
    $this->assertEquals(403, $response->getStatusCode());

    // Ensure still logged in.
    $response = $client->get($login_status_url, ['cookies' => $this->cookies]);
    $this->assertHttpResponse($response, 200, UserAuthenticationController::LOGGED_IN);

    // Try a logout request with correct token.
    $response = $this->logoutRequest($format, $logout_token);
    $this->assertEquals(204, $response->getStatusCode());

    // Ensure actually logged out.
    $response = $client->get($login_status_url, ['cookies' => $this->cookies]);
    $this->assertHttpResponse($response, 200, UserAuthenticationController::LOGGED_OUT);
  }

  /**
   * Gets the URL string for checking login for a given serialization format.
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

  /**
   * Do password reset testing for given format and account.
   *
   * @param string $format
   *   Serialization format.
   * @param \Drupal\user\UserInterface $account
   *   Test account.
   */
  protected function doTestPasswordReset($format, $account) {
    $response = $this->passwordRequest([], $format);
    $this->assertHttpResponseWithMessage($response, 400, 'Missing credentials.name or credentials.mail', $format);

    $response = $this->passwordRequest(['name' => 'dramallama'], $format);
    $this->assertEquals(200, $response->getStatusCode());

    $response = $this->passwordRequest(['mail' => 'llama@drupal.org'], $format);
    $this->assertEquals(200, $response->getStatusCode());

    $account
      ->block()
      ->save();

    $response = $this->passwordRequest(['name' => $account->getAccountName()], $format);
    $this->assertEquals(200, $response->getStatusCode());

    // Check that the proper warning has been logged.
    $arguments = [
      '%identifier' => $account->getAccountName(),
    ];
    $logged = Database::getConnection()->select('watchdog')
      ->fields('watchdog', ['variables'])
      ->condition('type', 'user')
      ->condition('message', 'Unable to send password reset email for blocked or not yet activated user %identifier.')
      ->orderBy('wid', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchField();
    $this->assertEquals(serialize($arguments), $logged);

    $response = $this->passwordRequest(['mail' => $account->getEmail()], $format);
    $this->assertEquals(200, $response->getStatusCode());

    // Check that the proper warning has been logged.
    $arguments = [
      '%identifier' => $account->getEmail(),
    ];

    $logged = Database::getConnection()->select('watchdog')
      ->fields('watchdog', ['variables'])
      ->condition('type', 'user')
      ->condition('message', 'Unable to send password reset email for blocked or not yet activated user %identifier.')
      ->orderBy('wid', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchField();
    $this->assertEquals(serialize($arguments), $logged);

    $account
      ->activate()
      ->save();

    $response = $this->passwordRequest(['name' => $account->getAccountName()], $format);
    $this->assertEquals(200, $response->getStatusCode());
    $this->loginFromResetEmail();
    $this->drupalLogout();

    $response = $this->passwordRequest(['mail' => $account->getEmail()], $format);
    $this->assertEquals(200, $response->getStatusCode());
    $this->loginFromResetEmail();
    $this->drupalLogout();
  }

  /**
   * Login from reset password email.
   */
  protected function loginFromResetEmail() {
    $_emails = $this->drupalGetMails();
    $email = end($_emails);
    $urls = [];
    preg_match('#.+user/reset/.+#', $email['body'], $urls);
    $resetURL = $urls[0];
    $this->drupalGet($resetURL);
    $this->submitForm([], 'Log in');
    $this->assertSession()->pageTextContains('You have just used your one-time login link. It is no longer necessary to use this link to log in. Please set your password.');
  }

}

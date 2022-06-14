<?php

namespace Drupal\Tests\user\Functional;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Core\Url;
use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use Drupal\Tests\rest\Functional\ResourceTestBase;
use Drupal\user\UserInterface;
use GuzzleHttp\RequestOptions;

/**
 * Tests registration of user using REST.
 *
 * @group user
 */
class UserRegistrationRestTest extends ResourceTestBase {

  use CookieResourceTestTrait;

  use AssertMailTrait {
    getMails as drupalGetMails;
  }

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

  /**
   * {@inheritdoc}
   */
  protected static $resourceConfigId = 'user_registration';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'rest'];

  /**
   * Entity type ID for this storage.
   *
   * @var string
   */
  protected static string $entityTypeId;

  const USER_EMAIL_DOMAIN = '@example.com';

  const TEST_EMAIL_DOMAIN = 'simpletest@example.com';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $auth = isset(static::$auth) ? [static::$auth] : [];
    $this->provisionResource([static::$format], $auth);

    $this->setUpAuthorization('POST');
  }

  /**
   * Tests that only anonymous users can register users.
   */
  public function testRegisterUser() {
    $config = $this->config('user.settings');

    // Test out different setting User Registration and Email Verification.
    // Allow visitors to register with no email verification.
    $config->set('register', UserInterface::REGISTER_VISITORS);
    $config->set('verify_mail', 0);
    $config->save();
    $user = $this->registerUser('Palmer.Eldritch');
    $this->assertFalse($user->isBlocked());
    $this->assertNotEmpty($user->getPassword());
    $email_count = count($this->drupalGetMails());

    $this->assertEquals(0, $email_count);

    // Attempt to register without sending a password.
    $response = $this->registerRequest('Rick.Deckard', FALSE);
    $this->assertResourceErrorResponse(422, "No password provided.", $response);

    // Attempt to register with a password when e-mail verification is on.
    $config->set('register', UserInterface::REGISTER_VISITORS);
    $config->set('verify_mail', 1);
    $config->save();
    $response = $this->registerRequest('Estraven');
    $this->assertResourceErrorResponse(422, 'A Password cannot be specified. It will be generated on login.', $response);

    // Allow visitors to register with email verification.
    $config->set('register', UserInterface::REGISTER_VISITORS);
    $config->set('verify_mail', 1);
    $config->save();
    $name = 'Jason.Taverner';
    $user = $this->registerUser($name, FALSE);
    $this->assertEmpty($user->getPassword());
    $this->assertTrue($user->isBlocked());
    $this->resetAll();

    $this->assertMailString('body', 'You may now log in by clicking this link', 1);

    // Allow visitors to register with Admin approval and no email verification.
    $config->set('register', UserInterface::REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL);
    $config->set('verify_mail', 0);
    $config->save();
    $name = 'Argaven';
    $user = $this->registerUser($name);
    $this->resetAll();
    $this->assertNotEmpty($user->getPassword());
    $this->assertTrue($user->isBlocked());
    $this->assertMailString('body', 'Your application for an account is', 2);
    $this->assertMailString('body', 'Argaven has applied for an account', 2);

    // Allow visitors to register with Admin approval and e-mail verification.
    $config->set('register', UserInterface::REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL);
    $config->set('verify_mail', 1);
    $config->save();
    $name = 'Bob.Arctor';
    $user = $this->registerUser($name, FALSE);
    $this->resetAll();
    $this->assertEmpty($user->getPassword());
    $this->assertTrue($user->isBlocked());

    $this->assertMailString('body', 'Your application for an account is', 2);
    $this->assertMailString('body', 'Bob.Arctor has applied for an account', 2);

    // Verify that an authenticated user cannot register a new user, despite
    // being granted permission to do so because only anonymous users can
    // register themselves, authenticated users with the necessary permissions
    // can POST a new user to the "user" REST resource.
    $this->initAuthentication();
    $response = $this->registerRequest($this->account->getAccountName());
    $this->assertResourceErrorResponse(403, "Only anonymous users can register a user.", $response);
  }

  /**
   * Create the request body.
   *
   * @param string $name
   *   Name.
   * @param bool $include_password
   *   Include Password.
   * @param bool $include_email
   *   Include Email.
   *
   * @return array
   *   Return the request body.
   */
  protected function createRequestBody($name, $include_password = TRUE, $include_email = TRUE) {
    $request_body = [
      'langcode' => [['value' => 'en']],
      'name' => [['value' => $name]],
    ];

    if ($include_email) {
      $request_body['mail'] = [['value' => $name . self::USER_EMAIL_DOMAIN]];
    }

    if ($include_password) {
      $request_body['pass']['value'] = 'SuperSecretPassword';
    }

    return $request_body;
  }

  /**
   * Helper function to generate the request body.
   *
   * @param array $request_body
   *   The request body array.
   *
   * @return array
   *   Return the request options.
   */
  protected function createRequestOptions(array $request_body) {
    $request_options = $this->getAuthenticationRequestOptions('POST');
    $request_options[RequestOptions::BODY] = $this->serializer->encode($request_body, static::$format);
    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;

    return $request_options;
  }

  /**
   * Registers a user via REST resource.
   *
   * @param string $name
   *   User name.
   * @param bool $include_password
   *   Include the password.
   * @param bool $include_email
   *   Include the email?
   *
   * @return bool|\Drupal\user\Entity\User
   *   Return bool or the user.
   */
  protected function registerUser($name, $include_password = TRUE, $include_email = TRUE) {
    // Verify that an anonymous user can register.
    $response = $this->registerRequest($name, $include_password, $include_email);
    $this->assertResourceResponse(200, FALSE, $response);
    $user = user_load_by_name($name);
    $this->assertNotEmpty($user, 'User was create as expected');
    return $user;
  }

  /**
   * Make a REST user registration request.
   *
   * @param string $name
   *   The name.
   * @param bool $include_password
   *   Include the password?
   * @param bool $include_email
   *   Include the email?
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   Return the Response.
   */
  protected function registerRequest($name, $include_password = TRUE, $include_email = TRUE) {
    $user_register_url = Url::fromRoute('user.register')
      ->setRouteParameter('_format', static::$format);
    $request_body = $this->createRequestBody($name, $include_password, $include_email);
    $request_options = $this->createRequestOptions($request_body);
    $response = $this->request('POST', $user_register_url, $request_options);

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'POST':
        $this->grantPermissionsToAuthenticatedRole(['restful post user_registration']);
        $this->grantPermissionsToAnonymousRole(['restful post user_registration']);
        break;

      default:
        throw new \UnexpectedValueException();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function assertNormalizationEdgeCases($method, Url $url, array $request_options): void {}

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessCacheability() {
    return new CacheableMetadata();
  }

}

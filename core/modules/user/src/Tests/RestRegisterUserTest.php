<?php

namespace Drupal\user\Tests;

use Drupal\rest\Tests\RESTTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests user registration via REST resource.
 *
 * @group user
 */
class RestRegisterUserTest extends RESTTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['hal'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->enableService('user_registration', 'POST', 'hal_json');

    Role::load(RoleInterface::ANONYMOUS_ID)
      ->grantPermission('restful post user_registration')
      ->save();

    Role::load(RoleInterface::AUTHENTICATED_ID)
      ->grantPermission('restful post user_registration')
      ->save();
  }

  /**
   * Tests that only anonymous users can register users.
   */
  public function testRegisterUser() {
    // Verify that an authenticated user cannot register a new user, despite
    // being granted permission to do so because only anonymous users can
    // register themselves, authenticated users with the necessary permissions
    // can POST a new user to the "user" REST resource.
    $user = $this->createUser();
    $this->drupalLogin($user);
    $this->registerRequest('palmer.eldritch');
    $this->assertResponse('403', 'Only anonymous users can register users.');
    $this->drupalLogout();

    $user_settings = $this->config('user.settings');

    // Test out different setting User Registration and Email Verification.
    // Allow visitors to register with no email verification.
    $user_settings->set('register', USER_REGISTER_VISITORS);
    $user_settings->set('verify_mail', 0);
    $user_settings->save();
    $user = $this->registerUser('Palmer.Eldritch');
    $this->assertFalse($user->isBlocked());
    $this->assertFalse(empty($user->getPassword()));
    $email_count = count($this->drupalGetMails());
    $this->assertEqual(0, $email_count);

    // Attempt to register without sending a password.
    $this->registerRequest('Rick.Deckard', FALSE);
    $this->assertResponse('422', 'No password provided');

    // Allow visitors to register with email verification.
    $user_settings->set('register', USER_REGISTER_VISITORS);
    $user_settings->set('verify_mail', 1);
    $user_settings->save();
    $user = $this->registerUser('Jason.Taverner', FALSE);
    $this->assertTrue(empty($user->getPassword()));
    $this->assertTrue($user->isBlocked());
    $this->assertMailString('body', 'You may now log in by clicking this link', 1);

    // Attempt to register with a password when e-mail verification is on.
    $this->registerRequest('Estraven', TRUE);
    $this->assertResponse('422', 'A Password cannot be specified. It will be generated on login.');

    // Allow visitors to register with Admin approval and e-mail verification.
    $user_settings->set('register', USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL);
    $user_settings->set('verify_mail', 1);
    $user_settings->save();
    $user = $this->registerUser('Bob.Arctor', FALSE);
    $this->assertTrue(empty($user->getPassword()));
    $this->assertTrue($user->isBlocked());
    $this->assertMailString('body', 'Your application for an account is', 2);
    $this->assertMailString('body', 'Bob.Arctor has applied for an account', 2);

    // Attempt to register with a password when e-mail verification is on.
    $this->registerRequest('Ursula', TRUE);
    $this->assertResponse('422', 'A Password cannot be specified. It will be generated on login.');

    // Allow visitors to register with Admin approval and no email verification.
    $user_settings->set('register', USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL);
    $user_settings->set('verify_mail', 0);
    $user_settings->save();
    $user = $this->registerUser('Argaven');
    $this->assertFalse(empty($user->getPassword()));
    $this->assertTrue($user->isBlocked());
    $this->assertMailString('body', 'Your application for an account is', 2);
    $this->assertMailString('body', 'Argaven has applied for an account', 2);

    // Attempt to register without sending a password.
    $this->registerRequest('Tibe', FALSE);
    $this->assertResponse('422', 'No password provided');
  }

  /**
   * Creates serialize user values.
   *
   * @param string $name
   *   The name of the user. Use only valid values for emails.
   *
   * @param bool $include_password
   *   Whether to include a password in the user values.
   *
   * @return string
   *   Serialized user values.
   */
  protected function createSerializedUser($name, $include_password = TRUE) {
    global $base_url;
    // New user info to be serialized.
    $data = [
      "_links" => ["type" => ["href" => $base_url . "/rest/type/user/user"]],
      "langcode" => [["value" => "en"]],
      "name" => [["value" => $name]],
      "mail" => [["value" => "$name@example.com"]],
    ];
    if ($include_password) {
      $data['pass']['value'] = 'SuperSecretPassword';
    }

    // Create a HAL+JSON version for the user entity we want to create.
    $serialized = $this->container->get('serializer')
      ->serialize($data, 'hal_json');
    return $serialized;
  }

  /**
   * Registers a user via REST resource.
   *
   * @param $name
   *   User name.
   *
   * @param bool $include_password
   *
   * @return bool|\Drupal\user\Entity\User
   */
  protected function registerUser($name, $include_password = TRUE) {
    // Verify that an anonymous user can register.
    $this->registerRequest($name, $include_password);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $user = user_load_by_name($name);
    $this->assertFalse(empty($user), 'User was create as expected');
    return $user;
  }

  /**
   * Make a REST user registration request.
   *
   * @param $name
   * @param $include_password
   */
  protected function registerRequest($name, $include_password = TRUE) {
    $serialized = $this->createSerializedUser($name, $include_password);
    $this->httpRequest('/user/register', 'POST', $serialized, 'application/hal+json');
  }

}

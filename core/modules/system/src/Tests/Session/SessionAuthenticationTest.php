<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Session\SessionAuthenticationTest.
 */

namespace Drupal\system\Tests\Session;

use Drupal\Core\Url;
use Drupal\basic_auth\Tests\BasicAuthTestTrait;
use Drupal\simpletest\WebTestBase;

/**
 * Tests if sessions are correctly handled when a user authenticates.
 *
 * @group Session
 */
class SessionAuthenticationTest extends WebTestBase {

  use BasicAuthTestTrait;

  /**
   * A test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['basic_auth', 'session_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a test administrator user.
    $this->user = $this->drupalCreateUser(['administer site configuration']);
  }

  /**
   * Check that a basic authentication session does not leak.
   *
   * Regression test for a bug that caused a session initiated by basic
   * authentication to persist over subsequent unauthorized requests.
   */
  public function testSessionFromBasicAuthenticationDoesNotLeak() {
    // This route is authorized through basic_auth only, not cookie.
    $protected_url = Url::fromRoute('session_test.get_session_basic_auth');

    // This route is not protected.
    $unprotected_url = Url::fromRoute('session_test.get_session_no_auth');

    // Test that the route is not accessible as an anonymous user.
    $this->drupalGet($protected_url);
    $this->assertResponse(401, 'An anonymous user cannot access a route protected with basic authentication.');

    // We should be able to access the route with basic authentication.
    $this->basicAuthGet($protected_url, $this->user->getUsername(), $this->user->pass_raw);
    $this->assertResponse(200, 'A route protected with basic authentication can be accessed by an authenticated user.');

    // Check that the correct user is logged in.
    $this->assertEqual($this->user->id(), json_decode($this->getRawContent())->user, 'The correct user is authenticated on a route with basic authentication.');

    // If we now try to access a page without basic authentication then we
    // should no longer be logged in.
    $this->drupalGet($unprotected_url);
    $this->assertResponse(200, 'An unprotected route can be accessed without basic authentication.');
    $this->assertFalse(json_decode($this->getRawContent())->user, 'The user is no longer authenticated after visiting a page without basic authentication.');

    // If we access the protected page again without basic authentication we
    // should get 401 Unauthorized.
    $this->drupalGet($protected_url);
    $this->assertResponse(401, 'A subsequent request to the same route without basic authentication is not authorized.');
  }

}

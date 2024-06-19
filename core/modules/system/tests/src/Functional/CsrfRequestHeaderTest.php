<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests protecting routes by requiring CSRF token in the request header.
 *
 * @group system
 */
class CsrfRequestHeaderTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'csrf_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests access to routes protected by CSRF request header requirements.
   *
   * This checks one route that uses _csrf_request_header_token.
   */
  public function testRouteAccess(): void {
    $client = $this->getHttpClient();
    $csrf_token_path = 'session/token';
    // Test using the current path.
    $route_name = 'csrf_test.protected';
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);

    $csrf_token = $this->drupalGet($csrf_token_path);
    $url = Url::fromRoute($route_name)
      ->setAbsolute(TRUE)
      ->toString();
    $post_options = [
      'headers' => ['Accept' => 'text/plain'],
      'http_errors' => FALSE,
    ];

    // Test that access is allowed for anonymous user with no token in header.
    $result = $client->post($url, $post_options);
    $this->assertEquals(200, $result->getStatusCode());

    // Add cookies to POST options so that all other requests are for the
    // authenticated user.
    $post_options['cookies'] = $this->getSessionCookies();

    // Test that access is denied with no token in header.
    $result = $client->post($url, $post_options);
    $this->assertEquals(403, $result->getStatusCode());

    // Test that access is allowed with correct token in header.
    $post_options['headers']['X-CSRF-Token'] = $csrf_token;
    $result = $client->post($url, $post_options);
    $this->assertEquals(200, $result->getStatusCode());

    // Test that access is denied with incorrect token in header.
    $post_options['headers']['X-CSRF-Token'] = 'this-is-not-the-token-you-are-looking-for';
    $result = $client->post($url, $post_options);
    $this->assertEquals(403, $result->getStatusCode());

  }

}

<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Routing;

use Drupal\Tests\BrowserTestBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the route cache when the request's query parameters are altered.
 *
 * This happens either in the normal course of operations or due to an
 * exception.
 *
 * @group routing
 */
class RouteCachingQueryAlteredTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['router_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // page_cache module is enabled in the testing profile, however by default
    // exceptions which create 4xx responses are cached for 1 hour. This is
    // undesirable for certain response types (e.g., 401) which vary on other
    // elements of the request than the URL. For this reason, do not cache 4xx
    // responses for the purposes of this test.
    $settings['settings']['cache_ttl_4xx'] = (object) [
      'value' => 0,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
  }

  /**
   * Tests route collection cache after an exception.
   */
  public function testRouteCollectionCacheAfterException(): void {
    // Force an exception early in the Kernel middleware on a cold cache by
    // simulating bad Bearer authentication.
    $this->drupalGet('/router-test/rejects-query-strings', [], [
      'Authorization' => 'Bearer invalid',
    ]);
    $this->assertSession()->statusCodeEquals(Response::HTTP_UNAUTHORIZED);
    // Check that the route collection cache does not recover any unexpected
    // query strings from the earlier request that involved an exception.
    // The requested controller returns 400 if there are any query parameters
    // present, similar to JSON:API paths that strictly filter requests.
    $this->drupalGet('/router-test/rejects-query-strings', [], [
      'Authorization' => 'Bearer valid',
    ]);
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
  }

}

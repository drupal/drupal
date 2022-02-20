<?php

namespace Drupal\Tests\hal\Functional\page_cache;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Enables the page cache and tests it with various HTTP requests.
 *
 * @group hal
 */
class PageCacheTest extends BrowserTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  protected $dumpHeaders = TRUE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['test_page_test', 'system_test', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config('system.site')
      ->set('name', 'Drupal')
      ->set('page.front', '/test-page')
      ->save();
  }

  /**
   * Tests support for different cache items with different request formats.
   *
   * Request formats are specified via a query parameter.
   */
  public function testQueryParameterFormatRequests() {
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 300);
    $config->save();

    // Enable REST support for nodes and hal+json.
    \Drupal::service('module_installer')->install([
      'node',
      'hal',
      'rest',
      'basic_auth',
    ]);
    $this->drupalCreateContentType(['type' => 'article']);
    $node = $this->drupalCreateNode(['type' => 'article']);
    $node_uri = $node->toUrl();
    $node_url_with_hal_json_format = $node->toUrl('canonical')->setRouteParameter('_format', 'hal_json');

    $this->drupalGet($node_uri);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    $this->assertSession()->responseHeaderEquals('Content-Type', 'text/html; charset=UTF-8');
    $this->drupalGet($node_uri);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    $this->assertSession()->responseHeaderEquals('Content-Type', 'text/html; charset=UTF-8');

    // Now request a HAL page twice, we expect that the first request is a cache
    // miss and both requests serve 'application/hal+json'.
    $this->drupalGet($node_url_with_hal_json_format);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    $this->assertSession()->responseHeaderEquals('Content-Type', 'application/hal+json');
    $this->drupalGet($node_url_with_hal_json_format);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    $this->assertSession()->responseHeaderEquals('Content-Type', 'application/hal+json');

    // Clear the page cache. After that request a double HAL request, followed
    // by two ordinary HTML ones.
    \Drupal::cache('page')->deleteAll();
    $this->drupalGet($node_url_with_hal_json_format);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    $this->assertSession()->responseHeaderEquals('Content-Type', 'application/hal+json');
    $this->drupalGet($node_url_with_hal_json_format);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    $this->assertSession()->responseHeaderEquals('Content-Type', 'application/hal+json');

    $this->drupalGet($node_uri);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    $this->assertSession()->responseHeaderEquals('Content-Type', 'text/html; charset=UTF-8');
    $this->drupalGet($node_uri);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    $this->assertSession()->responseHeaderEquals('Content-Type', 'text/html; charset=UTF-8');
  }

  /**
   * Retrieves only the headers for an absolute path.
   *
   * Executes a cURL request without any modifications to the given URL.
   * Note that Guzzle always normalizes URLs which prevents testing all
   * possible edge cases.
   *
   * @param string $url
   *   URL to request.
   *
   * @return array
   *   Array of headers.
   */
  protected function getHeaders($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch, CURLOPT_NOBODY, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_USERAGENT, drupal_generate_test_ua($this->databasePrefix));
    $output = curl_exec($ch);
    curl_close($ch);

    $headers = [];
    foreach (explode("\n", $output) as $header) {
      if (strpos($header, ':')) {
        [$key, $value] = explode(':', $header, 2);
        $headers[trim($key)] = trim($value);
      }
    }

    return $headers;
  }

}

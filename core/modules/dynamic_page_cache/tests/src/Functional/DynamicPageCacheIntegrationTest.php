<?php

namespace Drupal\Tests\dynamic_page_cache\Functional;

use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Url;
use Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber;
use Drupal\Tests\BrowserTestBase;

/**
 * Enables the Dynamic Page Cache and tests it in various scenarios.
 *
 * This does not test the self-healing of the redirect with conditional cache
 * contexts, because Dynamic Page Cache just reuses
 * \Drupal\Core\Render\RenderCache so that it doesn't have to implement and test
 * all of that again. It is tested in
 * RendererBubblingTest::testConditionalCacheContextBubblingSelfHealing().
 *
 * @group dynamic_page_cache
 *
 * @see \Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber
 */
class DynamicPageCacheIntegrationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dumpHeaders = TRUE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['dynamic_page_cache_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Uninstall the page_cache module; we want to test the Dynamic Page Cache
    // alone.
    \Drupal::service('module_installer')->uninstall(['page_cache']);
  }

  /**
   * Tests that Dynamic Page Cache works correctly, and verifies the edge cases.
   */
  public function testDynamicPageCache() {
    // Controllers returning plain response objects are ignored by Dynamic Page
    // Cache.
    $url = Url::fromUri('route:dynamic_page_cache_test.response');
    $this->drupalGet($url);
    $this->assertNull($this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER), 'Response object returned: Dynamic Page Cache is ignoring.');

    // Controllers returning CacheableResponseInterface (cacheable response)
    // objects are handled by Dynamic Page Cache.
    $url = Url::fromUri('route:dynamic_page_cache_test.cacheable_response');
    $this->drupalGet($url);
    $this->assertEqual('MISS', $this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER), 'Cacheable response object returned: Dynamic Page Cache is active, Dynamic Page Cache MISS.');
    $this->drupalGet($url);
    $this->assertEqual('HIT', $this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER), 'Cacheable response object returned: Dynamic Page Cache is active, Dynamic Page Cache HIT.');

    // Controllers returning render arrays, rendered as HTML responses, are
    // handled by Dynamic Page Cache.
    $url = Url::fromUri('route:dynamic_page_cache_test.html');
    $this->drupalGet($url);
    $this->assertEqual('MISS', $this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER), 'Render array returned, rendered as HTML response: Dynamic Page Cache is active, Dynamic Page Cache MISS.');
    $this->drupalGet($url);
    $this->assertEqual('HIT', $this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER), 'Render array returned, rendered as HTML response: Dynamic Page Cache is active, Dynamic Page Cache HIT.');

    // The above is the simple case, where the render array returned by the
    // response contains no cache contexts. So let's now test a route/controller
    // that *does* vary by a cache context whose value we can easily control: it
    // varies by the 'animal' query argument.
    foreach (['llama', 'piggy', 'unicorn', 'kitten'] as $animal) {
      $url = Url::fromUri('route:dynamic_page_cache_test.html.with_cache_contexts', ['query' => ['animal' => $animal]]);
      $this->drupalGet($url);
      $this->assertRaw($animal);
      $this->assertEqual('MISS', $this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER), 'Render array returned, rendered as HTML response: Dynamic Page Cache is active, Dynamic Page Cache MISS.');
      $this->drupalGet($url);
      $this->assertRaw($animal);
      $this->assertEqual('HIT', $this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER), 'Render array returned, rendered as HTML response: Dynamic Page Cache is active, Dynamic Page Cache HIT.');

      // Finally, let's also verify that the 'dynamic_page_cache_test.html'
      // route continued to see cache hits if we specify a query argument,
      // because it *should* ignore it and continue to provide Dynamic Page
      // Cache hits.
      $url = Url::fromUri('route:dynamic_page_cache_test.html', ['query' => ['animal' => 'piglet']]);
      $this->drupalGet($url);
      $this->assertEqual('HIT', $this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER), 'Render array returned, rendered as HTML response: Dynamic Page Cache is active, Dynamic Page Cache HIT.');
    }

    // Controllers returning render arrays, rendered as anything except a HTML
    // response, are ignored by Dynamic Page Cache (but only because those
    // wrapper formats' responses do not implement CacheableResponseInterface).
    $this->drupalGet('dynamic-page-cache-test/html', ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']]);
    $this->assertNull($this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER), 'Render array returned, rendered as AJAX response: Dynamic Page Cache is ignoring.');
    $this->drupalGet('dynamic-page-cache-test/html', ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_dialog']]);
    $this->assertNull($this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER), 'Render array returned, rendered as dialog response: Dynamic Page Cache is ignoring.');
    $this->drupalGet('dynamic-page-cache-test/html', ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_modal']]);
    $this->assertNull($this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER), 'Render array returned, rendered as modal response: Dynamic Page Cache is ignoring.');

    // Admin routes are ignored by Dynamic Page Cache.
    $this->drupalGet('dynamic-page-cache-test/html/admin');
    $this->assertNull($this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER), 'Response returned, rendered as HTML response, admin route: Dynamic Page Cache is ignoring');
    $this->drupalGet('dynamic-page-cache-test/response/admin');
    $this->assertNull($this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER), 'Response returned, plain response, admin route: Dynamic Page Cache is ignoring');
    $this->drupalGet('dynamic-page-cache-test/cacheable-response/admin');
    $this->assertNull($this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER), 'Response returned, cacheable response, admin route: Dynamic Page Cache is ignoring');

    // Max-age = 0 responses are ignored by Dynamic Page Cache.
    $this->drupalGet('dynamic-page-cache-test/html/uncacheable/max-age');
    $this->assertEqual('UNCACHEABLE', $this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER), 'Render array returned, rendered as HTML response, but uncacheable: Dynamic Page Cache is running, but not caching.');

    // 'user' cache context responses are ignored by Dynamic Page Cache.
    $this->drupalGet('dynamic-page-cache-test/html/uncacheable/contexts');
    $this->assertEqual('UNCACHEABLE', $this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER), 'Render array returned, rendered as HTML response, but uncacheable: Dynamic Page Cache is running, but not caching.');

    // 'current-temperature' cache tag responses are ignored by Dynamic Page
    // Cache.
    $this->drupalGet('dynamic-page-cache-test/html/uncacheable/tags');
    $this->assertEqual('MISS', $this->drupalGetHeader(DynamicPageCacheSubscriber::HEADER), 'By default, Drupal has no auto-placeholdering cache tags.');
  }

}

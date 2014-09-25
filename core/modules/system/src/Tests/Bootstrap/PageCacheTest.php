<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Bootstrap\PageCacheTest.
 */

namespace Drupal\system\Tests\Bootstrap;

use Drupal\Component\Datetime\DateTimePlus;
use Symfony\Component\Routing\RequestContext;
use Drupal\simpletest\WebTestBase;
use Drupal\Core\Cache\Cache;

/**
 * Enables the page cache and tests it with various HTTP requests.
 *
 * @group Bootstrap
 */
class PageCacheTest extends WebTestBase {

  protected $dumpHeaders = TRUE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('test_page_test', 'system_test');

  protected function setUp() {
    parent::setUp();

    \Drupal::config('system.site')
      ->set('name', 'Drupal')
      ->set('page.front', 'test-page')
      ->save();
  }

  /**
   * Test that cache tags are properly persisted.
   *
   * Since tag based invalidation works, we know that our tag properly
   * persisted.
   */
  function testPageCacheTags() {
    $config = \Drupal::config('system.performance');
    $config->set('cache.page.use_internal', 1);
    $config->set('cache.page.max_age', 300);
    $config->save();

    $path = 'system-test/cache_tags_page';
    $tags = array('system_test_cache_tags_page');
    $this->drupalGet($path);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');

    // Verify a cache hit, but also the presence of the correct cache tags.
    $this->drupalGet($path);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT');
    $cid_parts = array(url($path, array('absolute' => TRUE)), 'html');
    $cid = sha1(implode(':', $cid_parts));
    $cache_entry = \Drupal::cache('render')->get($cid);
    sort($cache_entry->tags);
    $expected_tags = array(
      'pre_render',
      'rendered',
      'system_test_cache_tags_page',
      'theme:stark',
      'theme_global_settings',
    );
    $this->assertIdentical($cache_entry->tags, $expected_tags);

    Cache::invalidateTags($tags);
    $this->drupalGet($path);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
  }

  /**
   * Tests support for different cache items with different Accept headers.
   */
  function testAcceptHeaderRequests() {
    $config = \Drupal::config('system.performance');
    $config->set('cache.page.use_internal', 1);
    $config->set('cache.page.max_age', 300);
    $config->save();

    $url_generator = \Drupal::urlGenerator();
    $url_generator->setContext(new RequestContext());
    $accept_header_cache_uri = $url_generator->getPathFromRoute('system_test.page_cache_accept_header');
    $json_accept_header = array('Accept: application/json');

    $this->drupalGet($accept_header_cache_uri);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS', 'HTML page was not yet cached.');
    $this->drupalGet($accept_header_cache_uri);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', 'HTML page was cached.');
    $this->assertRaw('<p>oh hai this is html.</p>', 'The correct HTML response was returned.');

    $this->drupalGet($accept_header_cache_uri, array(), $json_accept_header);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS', 'Json response was not yet cached.');
    $this->drupalGet($accept_header_cache_uri, array(), $json_accept_header);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', 'Json response was cached.');
    $this->assertRaw('{"content":"oh hai this is json"}', 'The correct Json response was returned.');
  }

  /**
   * Tests support of requests with If-Modified-Since and If-None-Match headers.
   */
  function testConditionalRequests() {
    $config = \Drupal::config('system.performance');
    $config->set('cache.page.use_internal', 1);
    $config->set('cache.page.max_age', 300);
    $config->save();

    // Fill the cache.
    $this->drupalGet('');
    // Verify the page is not printed twice when the cache is cold.
    $this->assertNoPattern('#<html.*<html#');

    $this->drupalHead('');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', 'Page was cached.');
    $etag = $this->drupalGetHeader('ETag');
    $last_modified = $this->drupalGetHeader('Last-Modified');

    $this->drupalGet('', array(), array('If-Modified-Since: ' . $last_modified, 'If-None-Match: ' . $etag));
    $this->assertResponse(304, 'Conditional request returned 304 Not Modified.');

    $this->drupalGet('', array(), array('If-Modified-Since: ' . gmdate(DATE_RFC822, strtotime($last_modified)), 'If-None-Match: ' . $etag));
    $this->assertResponse(304, 'Conditional request with obsolete If-Modified-Since date returned 304 Not Modified.');

    $this->drupalGet('', array(), array('If-Modified-Since: ' . gmdate(DATE_RFC850, strtotime($last_modified)), 'If-None-Match: ' . $etag));
    $this->assertResponse(304, 'Conditional request with obsolete If-Modified-Since date returned 304 Not Modified.');

    $this->drupalGet('', array(), array('If-Modified-Since: ' . $last_modified));
    // Verify the page is not printed twice when the cache is warm.
    $this->assertNoPattern('#<html.*<html#');
    $this->assertResponse(200, 'Conditional request without If-None-Match returned 200 OK.');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', 'Page was cached.');

    $this->drupalGet('', array(), array('If-Modified-Since: ' . gmdate(DateTimePlus::RFC7231, strtotime($last_modified) + 1), 'If-None-Match: ' . $etag));
    $this->assertResponse(200, 'Conditional request with new a If-Modified-Since date newer than Last-Modified returned 200 OK.');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', 'Page was cached.');

    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);
    $this->drupalGet('', array(), array('If-Modified-Since: ' . $last_modified, 'If-None-Match: ' . $etag));
    $this->assertResponse(200, 'Conditional request returned 200 OK for authenticated user.');
    $this->assertFalse($this->drupalGetHeader('X-Drupal-Cache'), 'Absense of Page was not cached.');
  }

  /**
   * Tests cache headers.
   */
  function testPageCache() {
    $config = \Drupal::config('system.performance');
    $config->set('cache.page.use_internal', 1);
    $config->set('cache.page.max_age', 300);
    $config->set('response.gzip', 1);
    $config->save();

    // Fill the cache.
    $this->drupalGet('system-test/set-header', array('query' => array('name' => 'Foo', 'value' => 'bar')));
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS', 'Page was not cached.');
    $this->assertEqual(strtolower($this->drupalGetHeader('Vary')), 'cookie,accept-encoding', 'Vary header was sent.');
    // Symfony's Response logic determines a specific order for the subvalues
    // of the Cache-Control header, even if they are explicitly passed in to
    // the response header bag in a different order.
    $this->assertEqual($this->drupalGetHeader('Cache-Control'), 'max-age=300, public', 'Cache-Control header was sent.');
    $this->assertEqual($this->drupalGetHeader('Expires'), 'Sun, 19 Nov 1978 05:00:00 GMT', 'Expires header was sent.');
    $this->assertEqual($this->drupalGetHeader('Foo'), 'bar', 'Custom header was sent.');

    // Check cache.
    $this->drupalGet('system-test/set-header', array('query' => array('name' => 'Foo', 'value' => 'bar')));
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', 'Page was cached.');
    $this->assertEqual(strtolower($this->drupalGetHeader('Vary')), 'cookie,accept-encoding', 'Vary: Cookie header was sent.');
    $this->assertEqual($this->drupalGetHeader('Cache-Control'), 'max-age=300, public', 'Cache-Control header was sent.');
    $this->assertEqual($this->drupalGetHeader('Expires'), 'Sun, 19 Nov 1978 05:00:00 GMT', 'Expires header was sent.');
    $this->assertEqual($this->drupalGetHeader('Foo'), 'bar', 'Custom header was sent.');

    // Check replacing default headers.
    $this->drupalGet('system-test/set-header', array('query' => array('name' => 'Expires', 'value' => 'Fri, 19 Nov 2008 05:00:00 GMT')));
    $this->assertEqual($this->drupalGetHeader('Expires'), 'Fri, 19 Nov 2008 05:00:00 GMT', 'Default header was replaced.');
    $this->drupalGet('system-test/set-header', array('query' => array('name' => 'Vary', 'value' => 'User-Agent')));
    $this->assertEqual(strtolower($this->drupalGetHeader('Vary')), 'user-agent,accept-encoding', 'Default header was replaced.');

    // Check that authenticated users bypass the cache.
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);
    $this->drupalGet('system-test/set-header', array('query' => array('name' => 'Foo', 'value' => 'bar')));
    $this->assertFalse($this->drupalGetHeader('X-Drupal-Cache'), 'Caching was bypassed.');
    $this->assertTrue(strpos(strtolower($this->drupalGetHeader('Vary')), 'cookie') === FALSE, 'Vary: Cookie header was not sent.');
    $this->assertEqual($this->drupalGetHeader('Cache-Control'), 'must-revalidate, no-cache, post-check=0, pre-check=0, private', 'Cache-Control header was sent.');
    $this->assertEqual($this->drupalGetHeader('Expires'), 'Sun, 19 Nov 1978 05:00:00 GMT', 'Expires header was sent.');
    $this->assertEqual($this->drupalGetHeader('Foo'), 'bar', 'Custom header was sent.');
  }

  /**
   * Tests the omit_vary_cookie setting.
   */
  public function testPageCacheWithoutVaryCookie() {
    $config = \Drupal::config('system.performance');
    $config->set('cache.page.use_internal', 1);
    $config->set('cache.page.max_age', 300);
    $config->save();

    $settings['settings']['omit_vary_cookie'] = (object) array(
      'value' => TRUE,
      'required' => TRUE,
    );
    $this->writeSettings($settings);

    // Fill the cache.
    $this->drupalGet('');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS', 'Page was not cached.');
    $this->assertTrue(strpos($this->drupalGetHeader('Vary'), 'Cookie') === FALSE, 'Vary: Cookie header was not sent.');

    // Check cache.
    $this->drupalGet('');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', 'Page was cached.');
    $this->assertTrue(strpos($this->drupalGetHeader('Vary'), 'Cookie') === FALSE, 'Vary: Cookie header was not sent.');
  }

  /**
   * Tests page compression.
   *
   * The test should pass even if zlib.output_compression is enabled in php.ini,
   * .htaccess or similar, or if compression is done outside PHP, e.g. by the
   * mod_deflate Apache module.
   */
  function testPageCompression() {
    $config = \Drupal::config('system.performance');
    $config->set('cache.page.use_internal', 1);
    $config->set('cache.page.max_age', 300);
    $config->set('response.gzip', 1);
    $config->save();

    // Fill the cache and verify that output is compressed.
    $this->drupalGet('', array(), array('Accept-Encoding: gzip,deflate'));
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS', 'Page was not cached.');
    $this->drupalSetContent(gzinflate(substr($this->drupalGetContent(), 10, -8)));
    $this->assertRaw('</html>', 'Page was gzip compressed.');

    // Verify that cached output is compressed.
    $this->drupalGet('', array(), array('Accept-Encoding: gzip,deflate'));
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', 'Page was cached.');
    $this->assertEqual($this->drupalGetHeader('Content-Encoding'), 'gzip', 'A Content-Encoding header was sent.');
    $this->drupalSetContent(gzinflate(substr($this->drupalGetContent(), 10, -8)));
    $this->assertRaw('</html>', 'Page was gzip compressed.');

    // Verify that a client without compression support gets an uncompressed page.
    $this->drupalGet('');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', 'Page was cached.');
    $this->assertFalse($this->drupalGetHeader('Content-Encoding'), 'A Content-Encoding header was not sent.');
    $this->assertTitle(t('Test page | @site-name', array('@site-name' => \Drupal::config('system.site')->get('name'))), 'Site title matches.');
    $this->assertRaw('</html>', 'Page was not compressed.');

    // Disable compression mode.
    $config->set('response.gzip', 0);
    $config->save();

    // Verify if cached page is still available for a client with compression support.
    $this->drupalGet('', array(), array('Accept-Encoding: gzip,deflate'));
    $this->drupalSetContent(gzinflate(substr($this->drupalGetContent(), 10, -8)));
    $this->assertRaw('</html>', 'Page was delivered after compression mode is changed (compression support enabled).');

    // Verify if cached page is still available for a client without compression support.
    $this->drupalGet('');
    $this->assertRaw('</html>', 'Page was delivered after compression mode is changed (compression support disabled).');
  }
}

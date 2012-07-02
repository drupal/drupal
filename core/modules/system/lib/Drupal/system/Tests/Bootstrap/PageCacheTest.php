<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Bootstrap\PageCacheTest.
 */

namespace Drupal\system\Tests\Bootstrap;

use Drupal\simpletest\WebTestBase;

/**
 * Enables the page cache and tests it with various HTTP requests.
 */
class PageCacheTest extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Page cache test',
      'description' => 'Enable the page cache and test it with various HTTP requests.',
      'group' => 'Bootstrap'
    );
  }

  function setUp() {
    parent::setUp(array('node', 'system_test'));

    config('system.site')
      ->set('name', 'Drupal')
      ->set('page.front', 'node')
      ->save();
  }

  /**
   * Test support for requests containing If-Modified-Since and If-None-Match headers.
   */
  function testConditionalRequests() {
    $config = config('system.performance');
    $config->set('cache', 1);
    $config->save();

    // Fill the cache.
    $this->drupalGet('');

    $this->drupalHead('');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', t('Page was cached.'));
    $etag = $this->drupalGetHeader('ETag');
    $last_modified = $this->drupalGetHeader('Last-Modified');

    $this->drupalGet('', array(), array('If-Modified-Since: ' . $last_modified, 'If-None-Match: ' . $etag));
    $this->assertResponse(304, t('Conditional request returned 304 Not Modified.'));

    $this->drupalGet('', array(), array('If-Modified-Since: ' . gmdate(DATE_RFC822, strtotime($last_modified)), 'If-None-Match: ' . $etag));
    $this->assertResponse(304, t('Conditional request with obsolete If-Modified-Since date returned 304 Not Modified.'));

    $this->drupalGet('', array(), array('If-Modified-Since: ' . gmdate(DATE_RFC850, strtotime($last_modified)), 'If-None-Match: ' . $etag));
    $this->assertResponse(304, t('Conditional request with obsolete If-Modified-Since date returned 304 Not Modified.'));

    $this->drupalGet('', array(), array('If-Modified-Since: ' . $last_modified));
    $this->assertResponse(200, t('Conditional request without If-None-Match returned 200 OK.'));
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', t('Page was cached.'));

    $this->drupalGet('', array(), array('If-Modified-Since: ' . gmdate(DATE_RFC1123, strtotime($last_modified) + 1), 'If-None-Match: ' . $etag));
    $this->assertResponse(200, t('Conditional request with new a If-Modified-Since date newer than Last-Modified returned 200 OK.'));
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', t('Page was cached.'));

    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);
    $this->drupalGet('', array(), array('If-Modified-Since: ' . $last_modified, 'If-None-Match: ' . $etag));
    $this->assertResponse(200, t('Conditional request returned 200 OK for authenticated user.'));
    $this->assertFalse($this->drupalGetHeader('X-Drupal-Cache'), t('Absense of Page was not cached.'));
  }

  /**
   * Test cache headers.
   */
  function testPageCache() {
    $config = config('system.performance');
    $config->set('cache', 1);
    $config->save();

    // Fill the cache.
    $this->drupalGet('system-test/set-header', array('query' => array('name' => 'Foo', 'value' => 'bar')));
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS', t('Page was not cached.'));
    $this->assertEqual($this->drupalGetHeader('Vary'), 'Cookie,Accept-Encoding', t('Vary header was sent.'));
    $this->assertEqual($this->drupalGetHeader('Cache-Control'), 'public, max-age=0', t('Cache-Control header was sent.'));
    $this->assertEqual($this->drupalGetHeader('Expires'), 'Sun, 19 Nov 1978 05:00:00 GMT', t('Expires header was sent.'));
    $this->assertEqual($this->drupalGetHeader('Foo'), 'bar', t('Custom header was sent.'));

    // Check cache.
    $this->drupalGet('system-test/set-header', array('query' => array('name' => 'Foo', 'value' => 'bar')));
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', t('Page was cached.'));
    $this->assertEqual($this->drupalGetHeader('Vary'), 'Cookie,Accept-Encoding', t('Vary: Cookie header was sent.'));
    $this->assertEqual($this->drupalGetHeader('Cache-Control'), 'public, max-age=0', t('Cache-Control header was sent.'));
    $this->assertEqual($this->drupalGetHeader('Expires'), 'Sun, 19 Nov 1978 05:00:00 GMT', t('Expires header was sent.'));
    $this->assertEqual($this->drupalGetHeader('Foo'), 'bar', t('Custom header was sent.'));

    // Check replacing default headers.
    $this->drupalGet('system-test/set-header', array('query' => array('name' => 'Expires', 'value' => 'Fri, 19 Nov 2008 05:00:00 GMT')));
    $this->assertEqual($this->drupalGetHeader('Expires'), 'Fri, 19 Nov 2008 05:00:00 GMT', t('Default header was replaced.'));
    $this->drupalGet('system-test/set-header', array('query' => array('name' => 'Vary', 'value' => 'User-Agent')));
    $this->assertEqual($this->drupalGetHeader('Vary'), 'User-Agent,Accept-Encoding', t('Default header was replaced.'));

    // Check that authenticated users bypass the cache.
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);
    $this->drupalGet('system-test/set-header', array('query' => array('name' => 'Foo', 'value' => 'bar')));
    $this->assertFalse($this->drupalGetHeader('X-Drupal-Cache'), t('Caching was bypassed.'));
    $this->assertTrue(strpos($this->drupalGetHeader('Vary'), 'Cookie') === FALSE, t('Vary: Cookie header was not sent.'));
    $this->assertEqual($this->drupalGetHeader('Cache-Control'), 'must-revalidate, no-cache, post-check=0, pre-check=0, private', t('Cache-Control header was sent.'));
    $this->assertEqual($this->drupalGetHeader('Expires'), 'Sun, 19 Nov 1978 05:00:00 GMT', t('Expires header was sent.'));
    $this->assertEqual($this->drupalGetHeader('Foo'), 'bar', t('Custom header was sent.'));

  }

  /**
   * Test page compression.
   *
   * The test should pass even if zlib.output_compression is enabled in php.ini,
   * .htaccess or similar, or if compression is done outside PHP, e.g. by the
   * mod_deflate Apache module.
   */
  function testPageCompression() {
    $config = config('system.performance');
    $config->set('cache', 1);
    $config->save();

    // Fill the cache and verify that output is compressed.
    $this->drupalGet('', array(), array('Accept-Encoding: gzip,deflate'));
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS', t('Page was not cached.'));
    $this->drupalSetContent(gzinflate(substr($this->drupalGetContent(), 10, -8)));
    $this->assertRaw('</html>', t('Page was gzip compressed.'));

    // Verify that cached output is compressed.
    $this->drupalGet('', array(), array('Accept-Encoding: gzip,deflate'));
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', t('Page was cached.'));
    $this->assertEqual($this->drupalGetHeader('Content-Encoding'), 'gzip', t('A Content-Encoding header was sent.'));
    $this->drupalSetContent(gzinflate(substr($this->drupalGetContent(), 10, -8)));
    $this->assertRaw('</html>', t('Page was gzip compressed.'));

    // Verify that a client without compression support gets an uncompressed page.
    $this->drupalGet('');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', t('Page was cached.'));
    $this->assertFalse($this->drupalGetHeader('Content-Encoding'), t('A Content-Encoding header was not sent.'));
    $this->assertTitle(t('Welcome to @site-name | @site-name', array('@site-name' => config('system.site')->get('name'))), t('Site title matches.'));
    $this->assertRaw('</html>', t('Page was not compressed.'));
  }
}

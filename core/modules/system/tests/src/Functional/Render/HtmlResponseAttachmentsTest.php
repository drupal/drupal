<?php

namespace Drupal\Tests\system\Functional\Render;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for HtmlResponseAttachmentsProcessor.
 *
 * @group Render
 */
class HtmlResponseAttachmentsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['render_attached_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test rendering of ['#attached'].
   */
  public function testAttachments() {
    // Test ['#attached']['http_header] = ['Status', $code].
    $this->drupalGet('/render_attached_test/teapot');
    $this->assertResponse(418);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    // Repeat for the cache.
    $this->drupalGet('/render_attached_test/teapot');
    $this->assertResponse(418);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');

    // Test ['#attached']['http_header'] with various replacement rules.
    $this->drupalGet('/render_attached_test/header');
    $this->assertTeapotHeaders();
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    // Repeat for the cache.
    $this->drupalGet('/render_attached_test/header');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');

    // Test ['#attached']['feed'].
    $this->drupalGet('/render_attached_test/feed');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    $this->assertFeed();
    // Repeat for the cache.
    $this->drupalGet('/render_attached_test/feed');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');

    // Test ['#attached']['html_head'].
    $this->drupalGet('/render_attached_test/head');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    $this->assertHead();
    // Repeat for the cache.
    $this->drupalGet('/render_attached_test/head');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');

    // Test ['#attached']['html_head_link'] when outputted as HTTP header.
    $this->drupalGet('/render_attached_test/html_header_link');
    $expected_link_headers = [
      '</foo?bar=&lt;baz&gt;&amp;baz=false>; rel="alternate"',
      '</foo/bar>; hreflang="nl"; rel="alternate"',
    ];
    $this->assertEqual($this->getSession()->getResponseHeaders()['Link'], $expected_link_headers);
  }

  /**
   * Test caching of ['#attached'].
   */
  public function testRenderCachedBlock() {
    // Make sure our test block is visible.
    $this->drupalPlaceBlock('attached_rendering_block', ['region' => 'content']);

    // Get the front page, which should now have our visible block.
    $this->drupalGet('');
    // Make sure our block is visible.
    $this->assertText('Markup from attached_rendering_block.');
    // Test that all our attached items are present.
    $this->assertFeed();
    $this->assertHead();
    $this->assertResponse(418);
    $this->assertTeapotHeaders();

    // Reload the page, to test caching.
    $this->drupalGet('');
    // Make sure our block is visible.
    $this->assertText('Markup from attached_rendering_block.');
    // The header should be present again.
    $this->assertSession()->responseHeaderEquals('X-Test-Teapot', 'Teapot Mode Active');
  }

  /**
   * Helper function to make assertions about added HTTP headers.
   */
  protected function assertTeapotHeaders() {
    $headers = $this->getSession()->getResponseHeaders();
    $this->assertEquals($headers['X-Test-Teapot'], ['Teapot Mode Active']);
    $this->assertEquals($headers['X-Test-Teapot-Replace'], ['Teapot replaced']);
    $this->assertEquals($headers['X-Test-Teapot-No-Replace'], ['This value is not replaced', 'This one is added']);
  }

  /**
   * Helper function to make assertions about the presence of an RSS feed.
   */
  protected function assertFeed() {
    // Discover the DOM element for the feed link.
    $test_meta = $this->xpath('//head/link[@href="test://url"]');
    $this->assertEqual(1, count($test_meta), 'Link has URL.');
    // Reconcile the other attributes.
    $test_meta_attributes = [
      'href' => 'test://url',
      'rel' => 'alternate',
      'type' => 'application/rss+xml',
      'title' => 'Your RSS feed.',
    ];
    $test_meta = reset($test_meta);
    if (empty($test_meta)) {
      $this->fail('Unable to find feed link.');
    }
    else {
      foreach ($test_meta_attributes as $attribute => $value) {
        $this->assertEquals($value, $test_meta->getAttribute($attribute));
      }
    }
  }

  /**
   * Helper function to make assertions about HTML head elements.
   */
  protected function assertHead() {
    // Discover the DOM element for the meta link.
    $test_meta = $this->xpath('//head/meta[@test-attribute="testvalue"]');
    $this->assertEqual(1, count($test_meta), 'There\'s only one test attribute.');
    // Grab the only DOM element.
    $test_meta = reset($test_meta);
    if (empty($test_meta)) {
      $this->fail('Unable to find the head meta.');
    }
    else {
      $this->assertEqual($test_meta->getAttribute('test-attribute'), 'testvalue');
    }
  }

}

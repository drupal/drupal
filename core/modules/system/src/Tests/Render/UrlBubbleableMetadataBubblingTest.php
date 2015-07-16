<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Render\UrlBubbleableMetadataBubblingTest.
 */

namespace Drupal\system\Tests\Render;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests that URL bubbleable metadata is correctly bubbled.
 *
 * @group Render
 */
class UrlBubbleableMetadataBubblingTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['cache_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->dumpHeaders = TRUE;
  }

  /**
   * Tests that URL bubbleable metadata is correctly bubbled.
   */
  public function testUrlBubbleableMetadataBubbling() {
    // Test that regular URLs bubble up bubbleable metadata when converted to
    // string.
    $url = Url::fromRoute('cache_test.url_bubbling');
    $this->drupalGet($url);
    $this->assertCacheContext('url.site');
    $this->assertRaw($url->setAbsolute()->toString());
  }

}

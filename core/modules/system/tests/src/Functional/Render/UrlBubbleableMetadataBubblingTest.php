<?php

namespace Drupal\Tests\system\Functional\Render;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests that URL bubbleable metadata is correctly bubbled.
 *
 * @group Render
 */
class UrlBubbleableMetadataBubblingTest extends BrowserTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['cache_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
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
    $this->assertSession()->responseContains($url->setAbsolute()->toString());
  }

}

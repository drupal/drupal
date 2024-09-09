<?php

declare(strict_types=1);

namespace Drupal\Tests\announcements_feed\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber;

/**
 * Defines a class for testing pages are still cacheable with dynamic page cache.
 *
 * @group announcements_feed
 */
final class AnnouncementsCacheTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'announcements_feed',
    'dynamic_page_cache',
    'node',
    'toolbar',
  ];

  /**
   * Tests dynamic page cache.
   */
  public function testDynamicPageCache(): void {
    $node_type = $this->drupalCreateContentType();
    $node = $this->drupalCreateNode(['type' => $node_type->id()]);
    $this->drupalLogin($this->drupalCreateUser([
      'access toolbar',
      'access announcements',
    ]));
    $this->drupalGet($node->toUrl());
    $this->assertSession()->responseHeaderEquals(DynamicPageCacheSubscriber::HEADER, 'MISS');
    // Reload the page, it should be cached now.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->elementExists('css', '[data-drupal-announce-trigger]');
    $this->assertSession()->responseHeaderEquals(DynamicPageCacheSubscriber::HEADER, 'HIT');
  }

}

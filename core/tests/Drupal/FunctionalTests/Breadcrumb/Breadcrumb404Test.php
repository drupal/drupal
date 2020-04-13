<?php

namespace Drupal\FunctionalTests\Breadcrumb;

use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the breadcrumb of 404 pages.
 *
 * @group breadcrumb
 */
class Breadcrumb404Test extends BrowserTestBase {

  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that different 404s don't create unnecessary cache entries.
   */
  public function testBreadcrumbOn404Pages() {
    $this->placeBlock('system_breadcrumb_block', ['id' => 'breadcrumb']);

    // Prime the cache first.
    $this->drupalGet('/not-found-1');
    $base_count = count($this->getBreadcrumbCacheEntries());

    $this->drupalGet('/not-found-2');
    $next_count = count($this->getBreadcrumbCacheEntries());
    $this->assertEquals($base_count, $next_count);

    $this->drupalGet('/not-found-3');
    $next_count = count($this->getBreadcrumbCacheEntries());
    $this->assertEquals($base_count, $next_count);
  }

  /**
   * Gets the breadcrumb cache entries.
   *
   * @return array
   *   The breadcrumb cache entries.
   */
  protected function getBreadcrumbCacheEntries() {
    $database = \Drupal::database();
    $cache_entries = $database->select('cache_render')
      ->fields('cache_render')
      ->condition('cid', $database->escapeLike('entity_view:block:breadcrumb') . '%', 'LIKE')
      ->execute()
      ->fetchAllAssoc('cid');
    return $cache_entries;
  }

}

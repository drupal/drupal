<?php

namespace Drupal\FunctionalTests\Breadcrumb;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
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
  public function testBreadcrumbCacheEntrieOn404Pages() {
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
   * Tests whether breadcrumbs can cause infinite recursion on 404 pages.
   */
  public function testBreadcrumbInfiniteRecursion() {
    \Drupal::service('module_installer')->install(['node', 'comment']);
    $this->placeBlock('system_breadcrumb_block', ['id' => 'breadcrumb']);

    NodeType::create([
      'type' => 'test',
    ])->save();
    Node::create([
      'type' => 'test',
      'title' => 'test',
      'status' => 1,
    ])->save();

    $this->drupalGet('/comment/reply/node/1/whatever/1');
    $this->assertSession()->statusCodeEquals(404);
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

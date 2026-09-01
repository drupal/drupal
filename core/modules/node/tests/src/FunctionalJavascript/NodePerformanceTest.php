<?php

declare(strict_types=1);

namespace Drupal\Tests\node\FunctionalJavascript;

use Drupal\Core\Cache\Cache;
use Drupal\FunctionalJavascriptTests\PerformanceTestBase;
use Drupal\node\NodeInterface;
use Drupal\Tests\node\Traits\PromotedContentViewTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the performance of node functionality including cache invalidation.
 *
 * This test focuses on node cache invalidation scenarios and the promoted
 * content view performance. Stark is used as the default theme so that this
 * test is not theme-specific.
 */
#[Group('node')]
#[Group('#slow')]
#[RequiresPhpExtension('apcu')]
#[RunTestsInSeparateProcesses]
class NodePerformanceTest extends PerformanceTestBase {

  use PromotedContentViewTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'views',
    'dynamic_page_cache',
    'page_cache',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable the promoted content view and rebuild routes.
    $this->enablePromotedContentView();

    // Create a test content type.
    $this->drupalCreateContentType(['type' => 'test_content', 'name' => 'Test Content']);

    // Create a node to be shown on the promoted content view.
    $this->drupalCreateNode([
      'type' => 'test_content',
      'promote' => NodeInterface::PROMOTED,
    ]);
  }

  /**
   * Tests performance of node-related functionality.
   */
  public function testNodePerformance(): void {
    $this->testPromotedContentPage();
    $this->testPromotedContentCacheInvalidation();
  }

  /**
   * Tests performance for the promoted content page view.
   */
  protected function testPromotedContentPage(): void {
    // Request the promoted content page, then immediately clear all object
    // caches, so that aggregates and image styles are created on disk but
    // otherwise caches are empty.
    $this->drupalGet('node');
    // Give time for big pipe placeholders, asset aggregate requests, and post
    // response tasks to finish processing and write to any caches before
    // clearing caches again.
    sleep(2);
    foreach (Cache::getBins() as $bin) {
      $bin->deleteAll();
    }
    // Now visit a different page to warm some caches.
    $this->drupalGet('user/login');
    // Ensure everything finishes before we collect performance data.
    sleep(2);

    // Test promoted content page.
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node');
    }, 'nodePromotedContentPage');
    $this->assertSame(0, $performance_data->getScriptBytes());

    $this->assertQueriesByName('nodePromotedContentPage', $performance_data->getQueries());
    $this->assertMetricsByName('nodePromotedContentPage', $performance_data);
    $expected_default_cache_cids = [
      'views_data:node_field_data:en',
      'views_data:en',
      'views_data:views:en',
      'views_data:node:en',
      'theme_registry:stark',
    ];
    $this->assertSame($expected_default_cache_cids, $performance_data->getCacheOperations()['get']['default']);
  }

  /**
   * Tests the impact of node cache invalidation on the promoted content view.
   */
  protected function testPromotedContentCacheInvalidation(): void {
    // Create a new node, this invalidates the node_list cache tag. Need to reset
    // the cache tag checksum service as it did not register a need to
    // invalidate that again.
    \Drupal::service('cache_tags.invalidator.checksum')->reset();
    $this->drupalCreateNode(['type' => 'test_content', 'title' => 'new page 2']);

    // Visit the promoted content page again.
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node');
    }, 'nodePromotedContentAfterInvalidation');

    $this->assertQueriesByName('nodePromotedContentAfterInvalidation', $performance_data->getQueries());
    $this->assertMetricsByName('nodePromotedContentAfterInvalidation', $performance_data);
    $expected_default_cache_cids = [
      'views_data:node_field_data:en',
      'views_data:views:en',
      'views_data:node:en',
    ];
    $this->assertSame($expected_default_cache_cids, $performance_data->getCacheOperations()['get']['default']);
  }

}

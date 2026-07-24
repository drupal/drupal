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
  protected static $modules = ['node', 'views'];

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

    $expected_queries = [
      'SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."alias" LIKE "/node" ESCAPE ' . "'\\\\'" . ') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC',
      'SELECT "name", "route", "fit" FROM "router" WHERE "pattern_outline" IN ( "/node" ) AND "number_parts" >= 1',
      'SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "views.view.promoted_content" )',
      'SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "views.settings" )',
      'SELECT "config"."name" AS "name" FROM "config" "config" WHERE ("collection" = "") AND ("name" LIKE "core.entity_view_mode.%" ESCAPE ' . "'\\\\'" . ') ORDER BY "collection" ASC, "name" ASC',
      'SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "core.entity_view_mode.node.full", "core.entity_view_mode.node.rss", "core.entity_view_mode.node.teaser", "core.entity_view_mode.user.compact", "core.entity_view_mode.user.full" )',
      'SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "views.view_route_names" ) AND "collection" = "state"',
      'SELECT "name", "route" FROM "router" WHERE "name" IN ( "view.promoted_content.feed_1" )',
      'SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "router.path_roots" ) AND "collection" = "state"',
      'SELECT 1 AS "expression" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."path" LIKE "/rss.xml%" ESCAPE ' . "'\\\\'" . ') LIMIT 1 OFFSET 0',
      'SELECT COUNT(*) AS "expression" FROM (SELECT 1 AS "expression" FROM "node_field_data" "node_field_data" WHERE ("node_field_data"."promote" = 1) AND ("node_field_data"."status" = 1)) "subquery"',
      'SELECT "node_field_data"."sticky" AS "node_field_data_sticky", "node_field_data"."created" AS "node_field_data_created", "node_field_data"."nid" AS "nid" FROM "node_field_data" "node_field_data" WHERE ("node_field_data"."promote" = 1) AND ("node_field_data"."status" = 1) ORDER BY "node_field_data_sticky" DESC, "node_field_data_created" DESC LIMIT 10 OFFSET 0',
      'SELECT "revision"."vid" AS "vid", "revision"."langcode" AS "langcode", "revision"."revision_uid" AS "revision_uid", "revision"."revision_timestamp" AS "revision_timestamp", "revision"."revision_log" AS "revision_log", "revision"."revision_default" AS "revision_default", "base"."nid" AS "nid", "base"."type" AS "type", "base"."uuid" AS "uuid", CASE "base"."vid" WHEN "revision"."vid" THEN 1 ELSE 0 END AS "isDefaultRevision" FROM "node" "base" INNER JOIN "node_revision" "revision" ON "revision"."vid" = "base"."vid" WHERE "base"."nid" IN (1)',
      'SELECT "node_field_data".*, "node_field_data"."langcode" AS "node_field_data__langcode", "node__body"."body_value" AS "body_value", "node__body"."body_format" AS "body_format" FROM "node_field_data" "node_field_data" LEFT OUTER JOIN "node__body" "node__body" ON "node__body"."entity_id" = "node_field_data"."nid" AND "node__body"."langcode" = "node_field_data"."langcode" AND "node__body"."deleted" = 0 WHERE "node_field_data"."nid" IN (1)',
      'SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "core.entity_view_display.node.test_content.teaser", "core.entity_view_display.node.test_content.default" )',
      'SELECT "config"."name" AS "name" FROM "config" "config" WHERE ("collection" = "") AND ("name" LIKE "node.type.%" ESCAPE ' . "'\\\\'" . ') ORDER BY "collection" ASC, "name" ASC',
      'SELECT "base"."uid" AS "uid", "base"."uuid" AS "uuid", "base"."langcode" AS "langcode" FROM "users" "base" WHERE "base"."uid" IN (0)',
      'SELECT "users_field_data".*, "users_field_data"."langcode" AS "users_field_data__langcode" FROM "users_field_data" "users_field_data" WHERE "users_field_data"."uid" IN (0)',
      'SELECT "user__roles"."entity_id" AS "id", "user__roles"."langcode" AS "langcode", "user__roles"."roles_target_id" AS "roles_target_id", "user__roles"."delta" AS "roles_delta" FROM "user__roles" "user__roles" WHERE ("user__roles"."entity_id" IN (0)) AND ("user__roles"."deleted" = 0) ORDER BY "user__roles"."delta" ASC',
      'SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "core.date_format.medium" )',
      'SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "core.date_format.long" )',
      'SELECT "name", "route" FROM "router" WHERE "name" IN ( "entity.node.canonical" )',
      'SELECT 1 AS "expression" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."path" LIKE "/node%" ESCAPE ' . "'\\\\'" . ') LIMIT 1 OFFSET 0',
      'SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "core.entity_view_display.user.user.compact", "core.entity_view_display.user.user.default" )',
      'SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "filter.format.plain_text" )',
      'SELECT "name", "route" FROM "router" WHERE "name" IN ( "view.promoted_content.page_1" )',
      'INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("state:Drupal\Core\Cache\CacheCollector", "LOCK_ID", "EXPIRE")',
      'DELETE FROM "semaphore"  WHERE ("name" = "state:Drupal\Core\Cache\CacheCollector") AND ("value" = "LOCK_ID")',
      'INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("theme_registry:runtime:stark:Drupal\Core\Cache\CacheCollector", "LOCK_ID", "EXPIRE")',
      'DELETE FROM "semaphore"  WHERE ("name" = "theme_registry:runtime:stark:Drupal\Core\Cache\CacheCollector") AND ("value" = "LOCK_ID")',
      'INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("library_info:stark:Drupal\Core\Cache\CacheCollector", "LOCK_ID", "EXPIRE")',
      'DELETE FROM "semaphore"  WHERE ("name" = "library_info:stark:Drupal\Core\Cache\CacheCollector") AND ("value" = "LOCK_ID")',
      'INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("path_alias_prefix_list:Drupal\Core\Cache\CacheCollector", "LOCK_ID", "EXPIRE")',
      'DELETE FROM "semaphore"  WHERE ("name" = "path_alias_prefix_list:Drupal\Core\Cache\CacheCollector") AND ("value" = "LOCK_ID")',
    ];
    $recorded_queries = $performance_data->getQueries();
    $this->assertSame($expected_queries, $recorded_queries);
    $expected = [
      'QueryCount' => 34,
      'CacheGetCount' => 82,
      'CacheGetCountByBin' => [
        'page' => 1,
        'config' => 18,
        'data' => 3,
        'discovery' => 31,
        'bootstrap' => 11,
        'dynamic_page_cache' => 1,
        'render' => 5,
        'default' => 5,
        'routes' => 4,
        'entity' => 2,
        'file_parsing' => 1,
      ],
      'CacheSetCount' => 41,
      'CacheSetCountByBin' => [
        'data' => 3,
        'config' => 8,
        'discovery' => 11,
        'default' => 2,
        'routes' => 3,
        'entity' => 2,
        'render' => 6,
        'dynamic_page_cache' => 2,
        'page' => 1,
        'bootstrap' => 3,
      ],
      'CacheDeleteCount' => 0,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 6,
      'CacheTagGroupedLookups' => [
        [
          'route_match',
          'access_policies',
          'routes',
          'router',
          'entity_types',
          'entity_field_info',
          'entity_bundles',
          'local_task',
          'library_info',
          'http_response',
        ],
        ['config:core.extension', 'views_data'],
        ['config:views.view.promoted_content', 'node:1', 'node_list'],
        ['rendered', 'user:0', 'user_view'],
        ['config:filter.format.plain_text', 'node_view'],
        ['config:user.role.anonymous'],
      ],
      'StylesheetCount' => 2,
      'StylesheetBytes' => 1450,
    ];
    $this->assertMetrics($expected, $performance_data);
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

    $expected_queries = [
      'SELECT COUNT(*) AS "expression" FROM (SELECT 1 AS "expression" FROM "node_field_data" "node_field_data" WHERE ("node_field_data"."promote" = 1) AND ("node_field_data"."status" = 1)) "subquery"',
      'SELECT "node_field_data"."sticky" AS "node_field_data_sticky", "node_field_data"."created" AS "node_field_data_created", "node_field_data"."nid" AS "nid" FROM "node_field_data" "node_field_data" WHERE ("node_field_data"."promote" = 1) AND ("node_field_data"."status" = 1) ORDER BY "node_field_data_sticky" DESC, "node_field_data_created" DESC LIMIT 10 OFFSET 0',
    ];
    $recorded_queries = $performance_data->getQueries();
    $this->assertSame($expected_queries, $recorded_queries);
    $expected = [
      'QueryCount' => 2,
      'CacheGetCount' => 57,
      'CacheGetCountByBin' => [
        'page' => 1,
        'config' => 10,
        'data' => 3,
        'discovery' => 20,
        'bootstrap' => 8,
        'dynamic_page_cache' => 2,
        'render' => 6,
        'default' => 3,
        'routes' => 3,
        'entity' => 1,
      ],
      'CacheSetCount' => 5,
      'CacheSetCountByBin' => [
        'data' => 1,
        'render' => 2,
        'dynamic_page_cache' => 1,
        'page' => 1,
      ],
      'CacheDeleteCount' => 0,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 3,
      'CacheTagGroupedLookups' => [
        [
          'config:filter.format.plain_text',
          'config:user.role.anonymous',
          'config:views.view.promoted_content',
          'http_response',
          'node:1',
          'node_list',
          'node_view',
          'rendered',
          'user:0',
          'user_view',
        ],
        [
          'route_match',
          'access_policies',
          'routes',
          'router',
          'entity_types',
          'entity_field_info',
          'entity_bundles',
          'local_task',
          'library_info',
        ],
        ['config:core.extension', 'views_data'],
      ],
      'StylesheetCount' => 2,
      'StylesheetBytes' => 1300,
    ];
    $this->assertMetrics($expected, $performance_data);
    $expected_default_cache_cids = [
      'views_data:node_field_data:en',
      'views_data:views:en',
      'views_data:node:en',
    ];
    $this->assertSame($expected_default_cache_cids, $performance_data->getCacheOperations()['get']['default']);
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\standard\FunctionalJavascript;

use Drupal\Core\Cache\Cache;
use Drupal\FunctionalJavascriptTests\PerformanceTestBase;
use Drupal\Tests\PerformanceData;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

// cSpell:ignore mlid

/**
 * Tests the performance of basic functionality in the standard profile.
 *
 * Stark is used as the default theme so that this test is not Olivero specific.
 *
 * @group Common
 * @group #slow
 * @requires extension apcu
 */
class StandardPerformanceTest extends PerformanceTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * The user account created during testing.
   */
  protected ?UserInterface $user = NULL;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create a node to be shown on the front page.
    $this->drupalCreateNode([
      'type' => 'article',
      'promote' => NodeInterface::PROMOTED,
    ]);
    // Grant the anonymous user the permission to look at user profiles.
    user_role_grant_permissions('anonymous', ['access user profiles']);
  }

  /**
   * Tests performance of the standard profile.
   */
  public function testStandardPerformance(): void {
    $this->testAnonymous();
    $this->testLogin();
    $this->testLoginBlock();
  }

  /**
   * Tests performance for anonymous users.
   */
  protected function testAnonymous(): void {
    // Request the front page, then immediately clear all object caches, so that
    // aggregates and image styles are created on disk but otherwise caches are
    // empty.
    $this->drupalGet('');
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

    // Test frontpage.
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('');
    }, 'standardFrontPage');
    $this->assertNoJavaScript($performance_data);

    $expected_queries = [
      'SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."alias" LIKE "/node" ESCAPE ' . "'\\\\'" . ') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC',
      'SELECT "name", "route", "fit" FROM "router" WHERE "pattern_outline" IN ( "/node" ) AND "number_parts" >= 1',
      'SELECT 1 AS "expression" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."path" LIKE "/rss.xml%" ESCAPE ' . "'\\\\'" . ') LIMIT 1 OFFSET 0',
      'SELECT COUNT(*) AS "expression" FROM (SELECT 1 AS "expression" FROM "node_field_data" "node_field_data" WHERE ("node_field_data"."promote" = 1) AND ("node_field_data"."status" = 1)) "subquery"',
      'SELECT "node_field_data"."sticky" AS "node_field_data_sticky", "node_field_data"."created" AS "node_field_data_created", "node_field_data"."nid" AS "nid" FROM "node_field_data" "node_field_data" WHERE ("node_field_data"."promote" = 1) AND ("node_field_data"."status" = 1) ORDER BY "node_field_data_sticky" DESC, "node_field_data_created" DESC LIMIT 10 OFFSET 0',
      'SELECT "revision"."vid" AS "vid", "revision"."langcode" AS "langcode", "revision"."revision_uid" AS "revision_uid", "revision"."revision_timestamp" AS "revision_timestamp", "revision"."revision_log" AS "revision_log", "revision"."revision_default" AS "revision_default", "base"."nid" AS "nid", "base"."type" AS "type", "base"."uuid" AS "uuid", CASE "base"."vid" WHEN "revision"."vid" THEN 1 ELSE 0 END AS "isDefaultRevision" FROM "node" "base" INNER JOIN "node_revision" "revision" ON "revision"."vid" = "base"."vid" WHERE "base"."nid" IN (1)',
      'SELECT "revision".* FROM "node_field_revision" "revision" WHERE ("revision"."nid" IN (1)) AND ("revision"."vid" IN ("1")) ORDER BY "revision"."nid" ASC',
      'SELECT "t".* FROM "node__body" "t" WHERE ("entity_id" IN (1)) AND ("deleted" = 0) AND ("langcode" IN ("en", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node__comment" "t" WHERE ("entity_id" IN (1)) AND ("deleted" = 0) AND ("langcode" IN ("en", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node__field_image" "t" WHERE ("entity_id" IN (1)) AND ("deleted" = 0) AND ("langcode" IN ("en", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node__field_tags" "t" WHERE ("entity_id" IN (1)) AND ("deleted" = 0) AND ("langcode" IN ("en", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "ces".* FROM "comment_entity_statistics" "ces" WHERE ("ces"."entity_id" IN (1)) AND ("ces"."entity_type" = "node")',
      'SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "core.entity_view_display.node.article.teaser", "core.entity_view_display.node.article.default" )',
      'SELECT "config"."name" AS "name" FROM "config" "config" WHERE ("collection" = "") AND ("name" LIKE "comment.type.%" ESCAPE ' . "'\\\\'" . ') ORDER BY "collection" ASC, "name" ASC',
      'SELECT "config"."name" AS "name" FROM "config" "config" WHERE ("collection" = "") AND ("name" LIKE "node.type.%" ESCAPE ' . "'\\\\'" . ') ORDER BY "collection" ASC, "name" ASC',
      'SELECT "base"."uid" AS "uid", "base"."uuid" AS "uuid", "base"."langcode" AS "langcode" FROM "users" "base" WHERE "base"."uid" IN (0)',
      'SELECT "data".* FROM "users_field_data" "data" WHERE "data"."uid" IN (0) ORDER BY "data"."uid" ASC',
      'SELECT "t".* FROM "user__roles" "t" WHERE ("entity_id" IN (0)) AND ("deleted" = 0) AND ("langcode" IN ("en", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "user__user_picture" "t" WHERE ("entity_id" IN (0)) AND ("deleted" = 0) AND ("langcode" IN ("en", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "core.date_format.medium" )',
      'SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "core.date_format.long" )',
      'SELECT 1 AS "expression" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."path" LIKE "/node%" ESCAPE ' . "'\\\\'" . ') LIMIT 1 OFFSET 0',
      'SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "core.entity_view_display.user.user.compact", "core.entity_view_display.user.user.default" )',
      'SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "filter.format.restricted_html" )',
      'SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "system.image" )',
      'SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "user.role.authenticated" )',
      'SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "theme:stark" ) AND "collection" = "config.entity.key_store.block"',
      'SELECT "menu_tree"."menu_name" AS "menu_name", "menu_tree"."route_name" AS "route_name", "menu_tree"."route_parameters" AS "route_parameters", "menu_tree"."url" AS "url", "menu_tree"."title" AS "title", "menu_tree"."description" AS "description", "menu_tree"."parent" AS "parent", "menu_tree"."weight" AS "weight", "menu_tree"."options" AS "options", "menu_tree"."expanded" AS "expanded", "menu_tree"."enabled" AS "enabled", "menu_tree"."provider" AS "provider", "menu_tree"."metadata" AS "metadata", "menu_tree"."class" AS "class", "menu_tree"."form_class" AS "form_class", "menu_tree"."id" AS "id" FROM "menu_tree" "menu_tree" WHERE ("route_name" = "view.frontpage.page_1") AND ("route_param_key" = "view_id=frontpage&display_id=page_1") AND ("menu_name" = "main") ORDER BY "depth" ASC, "weight" ASC, "id" ASC',
      'SELECT "menu_tree"."menu_name" AS "menu_name", "menu_tree"."route_name" AS "route_name", "menu_tree"."route_parameters" AS "route_parameters", "menu_tree"."url" AS "url", "menu_tree"."title" AS "title", "menu_tree"."description" AS "description", "menu_tree"."parent" AS "parent", "menu_tree"."weight" AS "weight", "menu_tree"."options" AS "options", "menu_tree"."expanded" AS "expanded", "menu_tree"."enabled" AS "enabled", "menu_tree"."provider" AS "provider", "menu_tree"."metadata" AS "metadata", "menu_tree"."class" AS "class", "menu_tree"."form_class" AS "form_class", "menu_tree"."id" AS "id" FROM "menu_tree" "menu_tree" WHERE ("route_name" = "<front>") AND ("route_param_key" = "") AND ("menu_name" = "main") ORDER BY "depth" ASC, "weight" ASC, "id" ASC',
      'SELECT "menu_tree"."p1" AS "p1", "menu_tree"."p2" AS "p2", "menu_tree"."p3" AS "p3", "menu_tree"."p4" AS "p4", "menu_tree"."p5" AS "p5", "menu_tree"."p6" AS "p6", "menu_tree"."p7" AS "p7", "menu_tree"."p8" AS "p8", "menu_tree"."p9" AS "p9" FROM "menu_tree" "menu_tree" WHERE "id" = "standard.front_page"',
      'SELECT "menu_tree"."id" AS "id" FROM "menu_tree" "menu_tree" WHERE "mlid" IN ("5") ORDER BY "depth" DESC',
      'SELECT "menu_tree"."menu_name" AS "menu_name", "menu_tree"."route_name" AS "route_name", "menu_tree"."route_parameters" AS "route_parameters", "menu_tree"."url" AS "url", "menu_tree"."title" AS "title", "menu_tree"."description" AS "description", "menu_tree"."parent" AS "parent", "menu_tree"."weight" AS "weight", "menu_tree"."options" AS "options", "menu_tree"."expanded" AS "expanded", "menu_tree"."enabled" AS "enabled", "menu_tree"."provider" AS "provider", "menu_tree"."metadata" AS "metadata", "menu_tree"."class" AS "class", "menu_tree"."form_class" AS "form_class", "menu_tree"."id" AS "id" FROM "menu_tree" "menu_tree" WHERE ("route_name" = "view.frontpage.page_1") AND ("route_param_key" = "view_id=frontpage&display_id=page_1") AND ("menu_name" = "account") ORDER BY "depth" ASC, "weight" ASC, "id" ASC',
      'SELECT "menu_tree"."menu_name" AS "menu_name", "menu_tree"."route_name" AS "route_name", "menu_tree"."route_parameters" AS "route_parameters", "menu_tree"."url" AS "url", "menu_tree"."title" AS "title", "menu_tree"."description" AS "description", "menu_tree"."parent" AS "parent", "menu_tree"."weight" AS "weight", "menu_tree"."options" AS "options", "menu_tree"."expanded" AS "expanded", "menu_tree"."enabled" AS "enabled", "menu_tree"."provider" AS "provider", "menu_tree"."metadata" AS "metadata", "menu_tree"."class" AS "class", "menu_tree"."form_class" AS "form_class", "menu_tree"."id" AS "id" FROM "menu_tree" "menu_tree" WHERE ("route_name" = "<front>") AND ("route_param_key" = "") AND ("menu_name" = "account") ORDER BY "depth" ASC, "weight" ASC, "id" ASC',
      'SELECT "menu_tree".* FROM "menu_tree" "menu_tree" WHERE ("menu_name" = "main") AND ("depth" <= 2) ORDER BY "p1" ASC, "p2" ASC, "p3" ASC, "p4" ASC, "p5" ASC, "p6" ASC, "p7" ASC, "p8" ASC, "p9" ASC',
      'INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("theme_registry:runtime:stark:Drupal\Core\Utility\ThemeRegistry", "LOCK_ID", "EXPIRE")',
      'DELETE FROM "semaphore"  WHERE ("name" = "theme_registry:runtime:stark:Drupal\Core\Utility\ThemeRegistry") AND ("value" = "LOCK_ID")',
      'INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("library_info:stark:Drupal\Core\Cache\CacheCollector", "LOCK_ID", "EXPIRE")',
      'DELETE FROM "semaphore"  WHERE ("name" = "library_info:stark:Drupal\Core\Cache\CacheCollector") AND ("value" = "LOCK_ID")',
      'INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("path_alias_prefix_list:Drupal\Core\Cache\CacheCollector", "LOCK_ID", "EXPIRE")',
      'DELETE FROM "semaphore"  WHERE ("name" = "path_alias_prefix_list:Drupal\Core\Cache\CacheCollector") AND ("value" = "LOCK_ID")',
      'INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("active-trail:route:view.frontpage.page_1:route_parameters:a:2:{s:10:"display_id";s:6:"page_1";s:7:"view_id";s:9:"frontpage";}:Drupal\Core\Cache\CacheCollector", "LOCK_ID", "EXPIRE")',
      'DELETE FROM "semaphore"  WHERE ("name" = "active-trail:route:view.frontpage.page_1:route_parameters:a:2:{s:10:"display_id";s:6:"page_1";s:7:"view_id";s:9:"frontpage";}:Drupal\Core\Cache\CacheCollector") AND ("value" = "LOCK_ID")',
    ];
    $recorded_queries = $performance_data->getQueries();
    $this->assertSame($expected_queries, $recorded_queries);
    $expected = [
      'QueryCount' => 42,
      'CacheGetCount' => 100,
      'CacheGetCountByBin' => [
        'page' => 1,
        'config' => 21,
        'data' => 8,
        'discovery' => 38,
        'bootstrap' => 8,
        'dynamic_page_cache' => 1,
        'render' => 13,
        'default' => 5,
        'entity' => 2,
        'menu' => 3,
      ],
      'CacheSetCount' => 47,
      'CacheDeleteCount' => 0,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 16,
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
        ],
        ['config:views.view.frontpage'],
        ['config:core.extension', 'views_data'],
        ['node:1', 'node_list'],
        ['rendered', 'user:0', 'user_view'],
        ['config:filter.format.restricted_html', 'node_view'],
        [
          'block_view',
          'config:block.block.stark_site_branding',
          'config:system.site',
        ],
        [
          'CACHE_MISS_IF_UNCACHEABLE_HTTP_METHOD:form',
          'config:block.block.stark_search_form_narrow',
          'config:search.settings',
        ],
        ['config:block.block.stark_search_form_wide'],
        ['config:block.block.stark_messages'],
        ['config:block.block.stark_help'],
        ['config:block.block.stark_powered'],
        [
          'config:block.block.stark_account_menu',
          'config:block.block.stark_breadcrumbs',
          'config:block.block.stark_content',
          'config:block.block.stark_main_menu',
          'config:block.block.stark_page_title',
          'config:block.block.stark_primary_admin_actions',
          'config:block.block.stark_primary_local_tasks',
          'config:block.block.stark_secondary_local_tasks',
          'config:block_list',
          'http_response',
        ],
        ['config:system.menu.account'],
        ['config:system.menu.main'],
        ['config:user.role.anonymous'],
      ],
      'StylesheetCount' => 1,
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

    // Test node page.
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'standardNodePage');
    $this->assertNoJavaScript($performance_data);

    $expected_queries = [
      'SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."alias" LIKE "/node/1" ESCAPE ' . "'\\\\'" . ') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC',
      'SELECT "name", "route", "fit" FROM "router" WHERE "pattern_outline" IN ( "/node/1", "/node/%", "/node" ) AND "number_parts" >= 2',
      'SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "core.entity_view_display.node.article.full" )',
      'SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "theme:stark" ) AND "collection" = "config.entity.key_store.block"',
      'SELECT "menu_tree"."menu_name" AS "menu_name", "menu_tree"."route_name" AS "route_name", "menu_tree"."route_parameters" AS "route_parameters", "menu_tree"."url" AS "url", "menu_tree"."title" AS "title", "menu_tree"."description" AS "description", "menu_tree"."parent" AS "parent", "menu_tree"."weight" AS "weight", "menu_tree"."options" AS "options", "menu_tree"."expanded" AS "expanded", "menu_tree"."enabled" AS "enabled", "menu_tree"."provider" AS "provider", "menu_tree"."metadata" AS "metadata", "menu_tree"."class" AS "class", "menu_tree"."form_class" AS "form_class", "menu_tree"."id" AS "id" FROM "menu_tree" "menu_tree" WHERE ("route_name" = "entity.node.canonical") AND ("route_param_key" = "node=1") AND ("menu_name" = "main") ORDER BY "depth" ASC, "weight" ASC, "id" ASC',
      'SELECT "menu_tree"."menu_name" AS "menu_name", "menu_tree"."route_name" AS "route_name", "menu_tree"."route_parameters" AS "route_parameters", "menu_tree"."url" AS "url", "menu_tree"."title" AS "title", "menu_tree"."description" AS "description", "menu_tree"."parent" AS "parent", "menu_tree"."weight" AS "weight", "menu_tree"."options" AS "options", "menu_tree"."expanded" AS "expanded", "menu_tree"."enabled" AS "enabled", "menu_tree"."provider" AS "provider", "menu_tree"."metadata" AS "metadata", "menu_tree"."class" AS "class", "menu_tree"."form_class" AS "form_class", "menu_tree"."id" AS "id" FROM "menu_tree" "menu_tree" WHERE ("route_name" = "entity.node.canonical") AND ("route_param_key" = "node=1") AND ("menu_name" = "account") ORDER BY "depth" ASC, "weight" ASC, "id" ASC',
      'INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("theme_registry:runtime:stark:Drupal\Core\Utility\ThemeRegistry", "LOCK_ID", "EXPIRE")',
      'DELETE FROM "semaphore"  WHERE ("name" = "theme_registry:runtime:stark:Drupal\Core\Utility\ThemeRegistry") AND ("value" = "LOCK_ID")',
      'INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("active-trail:route:entity.node.canonical:route_parameters:a:1:{s:4:"node";s:1:"1";}:Drupal\Core\Cache\CacheCollector", "LOCK_ID", "EXPIRE")',
      'DELETE FROM "semaphore"  WHERE ("name" = "active-trail:route:entity.node.canonical:route_parameters:a:1:{s:4:"node";s:1:"1";}:Drupal\Core\Cache\CacheCollector") AND ("value" = "LOCK_ID")',
    ];
    $recorded_queries = $performance_data->getQueries();
    $this->assertSame($expected_queries, $recorded_queries);
    $expected = [
      'QueryCount' => 10,
      'CacheGetCount' => 71,
      'CacheSetCount' => 16,
      'CacheDeleteCount' => 0,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 13,
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
        ],
        ['rendered', 'user:0', 'user_view'],
        ['config:filter.format.restricted_html', 'node:1', 'node_view'],
        [
          'block_view',
          'config:block.block.stark_site_branding',
          'config:system.site',
        ],
        [
          'CACHE_MISS_IF_UNCACHEABLE_HTTP_METHOD:form',
          'config:block.block.stark_search_form_narrow',
          'config:search.settings',
        ],
        ['config:block.block.stark_search_form_wide'],
        ['config:block.block.stark_messages'],
        ['config:block.block.stark_help'],
        ['config:block.block.stark_powered'],
        [
          'config:block.block.stark_account_menu',
          'config:block.block.stark_breadcrumbs',
          'config:block.block.stark_content',
          'config:block.block.stark_main_menu',
          'config:block.block.stark_page_title',
          'config:block.block.stark_primary_admin_actions',
          'config:block.block.stark_primary_local_tasks',
          'config:block.block.stark_secondary_local_tasks',
          'config:block_list',
          'http_response',
        ],
        ['config:system.menu.main'],
        ['config:system.menu.account'],
        ['config:user.role.anonymous'],
      ],
      'StylesheetCount' => 1,
      'StylesheetBytes' => 1500,
    ];
    $this->assertMetrics($expected, $performance_data);

    // Test user profile page.
    $this->user = $this->drupalCreateUser();
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('user/' . $this->user->id());
    }, 'standardUserPage');
    $this->assertNoJavaScript($performance_data);

    $expected_queries = [
      'SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."alias" LIKE "/user/2" ESCAPE ' . "'\\\\'" . ') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC',
      'SELECT "name", "route", "fit" FROM "router" WHERE "pattern_outline" IN ( "/user/2", "/user/%", "/user" ) AND "number_parts" >= 2',
      'SELECT "base"."uid" AS "uid", "base"."uuid" AS "uuid", "base"."langcode" AS "langcode" FROM "users" "base" WHERE "base"."uid" IN (2)',
      'SELECT "data".* FROM "users_field_data" "data" WHERE "data"."uid" IN (2) ORDER BY "data"."uid" ASC',
      'SELECT "t".* FROM "user__roles" "t" WHERE ("entity_id" IN (2)) AND ("deleted" = 0) AND ("langcode" IN ("en", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "user__user_picture" "t" WHERE ("entity_id" IN (2)) AND ("deleted" = 0) AND ("langcode" IN ("en", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "core.entity_view_display.user.user.full" )',
      'SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "theme:stark" ) AND "collection" = "config.entity.key_store.block"',
      'SELECT "menu_tree"."menu_name" AS "menu_name", "menu_tree"."route_name" AS "route_name", "menu_tree"."route_parameters" AS "route_parameters", "menu_tree"."url" AS "url", "menu_tree"."title" AS "title", "menu_tree"."description" AS "description", "menu_tree"."parent" AS "parent", "menu_tree"."weight" AS "weight", "menu_tree"."options" AS "options", "menu_tree"."expanded" AS "expanded", "menu_tree"."enabled" AS "enabled", "menu_tree"."provider" AS "provider", "menu_tree"."metadata" AS "metadata", "menu_tree"."class" AS "class", "menu_tree"."form_class" AS "form_class", "menu_tree"."id" AS "id" FROM "menu_tree" "menu_tree" WHERE ("route_name" = "entity.user.canonical") AND ("route_param_key" = "user=2") AND ("menu_name" = "main") ORDER BY "depth" ASC, "weight" ASC, "id" ASC',
      'SELECT "menu_tree"."menu_name" AS "menu_name", "menu_tree"."route_name" AS "route_name", "menu_tree"."route_parameters" AS "route_parameters", "menu_tree"."url" AS "url", "menu_tree"."title" AS "title", "menu_tree"."description" AS "description", "menu_tree"."parent" AS "parent", "menu_tree"."weight" AS "weight", "menu_tree"."options" AS "options", "menu_tree"."expanded" AS "expanded", "menu_tree"."enabled" AS "enabled", "menu_tree"."provider" AS "provider", "menu_tree"."metadata" AS "metadata", "menu_tree"."class" AS "class", "menu_tree"."form_class" AS "form_class", "menu_tree"."id" AS "id" FROM "menu_tree" "menu_tree" WHERE ("route_name" = "entity.user.canonical") AND ("route_param_key" = "user=2") AND ("menu_name" = "account") ORDER BY "depth" ASC, "weight" ASC, "id" ASC',
      'SELECT "ud".* FROM "users_data" "ud" WHERE ("module" = "contact") AND ("uid" = "2") AND ("name" = "enabled")',
      'SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "contact.settings" )',
      'INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("active-trail:route:entity.user.canonical:route_parameters:a:1:{s:4:"user";s:1:"2";}:Drupal\Core\Cache\CacheCollector", "LOCK_ID", "EXPIRE")',
      'DELETE FROM "semaphore"  WHERE ("name" = "active-trail:route:entity.user.canonical:route_parameters:a:1:{s:4:"user";s:1:"2";}:Drupal\Core\Cache\CacheCollector") AND ("value" = "LOCK_ID")',
    ];
    $recorded_queries = $performance_data->getQueries();
    $this->assertSame($expected_queries, $recorded_queries);
    $expected = [
      'QueryCount' => 14,
      'CacheGetCount' => 56,
      'CacheSetCount' => 17,
      'CacheDeleteCount' => 0,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 12,
      'StylesheetCount' => 1,
      'StylesheetBytes' => 1150,
    ];
    $this->assertMetrics($expected, $performance_data);
  }

  /**
   * Tests the performance of logging in.
   */
  protected function testLogin(): void {
    // Create a user and log them in to warm all caches. Manually submit the
    // form so that we repeat the same steps when recording performance data. Do
    // this twice so that any caches which take two requests to warm are also
    // covered.
    for ($i = 0; $i < 2; $i++) {
      $this->drupalGet('node');
      $this->drupalGet('user/login');
      $this->submitLoginForm($this->user);
      $this->drupalLogout();
    }

    $this->drupalGet('node');
    $this->drupalGet('user/login');
    $performance_data = $this->collectPerformanceData(function () {
      $this->submitLoginForm($this->user);
    }, 'standardLogin');

    $expected_queries = [
      'SELECT "name", "value" FROM "key_value_expire" WHERE "expire" > "NOW" AND "name" IN ( "KEY" ) AND "collection" = "form"',
      'SELECT COUNT(*) AS "expression" FROM (SELECT 1 AS "expression" FROM "flood" "f" WHERE ("event" = "user.failed_login_ip") AND ("identifier" = "CLIENT_IP") AND ("timestamp" > "TIMESTAMP")) "subquery"',
      'SELECT "base_table"."uid" AS "uid", "base_table"."uid" AS "base_table_uid" FROM "users" "base_table" INNER JOIN "users_field_data" "users_field_data" ON "users_field_data"."uid" = "base_table"."uid" WHERE ("users_field_data"."name" IN ("ACCOUNT_NAME")) AND ("users_field_data"."default_langcode" IN (1))',
      'SELECT COUNT(*) AS "expression" FROM (SELECT 1 AS "expression" FROM "flood" "f" WHERE ("event" = "user.failed_login_user") AND ("identifier" = "CLIENT_IP") AND ("timestamp" > "TIMESTAMP")) "subquery"',
      'INSERT INTO "watchdog" ("uid", "type", "message", "variables", "severity", "link", "location", "referer", "hostname", "timestamp") VALUES ("2", "user", "Session opened for %name.", "WATCHDOG_DATA", 6, "", "LOCATION", "REFERER", "CLIENT_IP", "TIMESTAMP")',
      'UPDATE "users_field_data" SET "login"="TIMESTAMP" WHERE "uid" = "2"',
      'SELECT "session" FROM "sessions" WHERE "sid" = "SESSION_ID" LIMIT 0, 1',
      'SELECT 1 AS "expression" FROM "sessions" "sessions" WHERE "sid" = "SESSION_ID"',
      'INSERT INTO "sessions" ("sid", "uid", "hostname", "session", "timestamp") VALUES ("SESSION_ID", "2", "CLIENT_IP", "SESSION_DATA", "TIMESTAMP")',
      'SELECT "session" FROM "sessions" WHERE "sid" = "SESSION_ID" LIMIT 0, 1',
      'SELECT * FROM "users_field_data" "u" WHERE "u"."uid" = "2" AND "u"."default_langcode" = 1',
      'SELECT "roles_target_id" FROM "user__roles" WHERE "entity_id" = "2"',
      'SELECT "base"."uid" AS "uid", "base"."uuid" AS "uuid", "base"."langcode" AS "langcode" FROM "users" "base" WHERE "base"."uid" IN (2)',
      'SELECT "data".* FROM "users_field_data" "data" WHERE "data"."uid" IN (2) ORDER BY "data"."uid" ASC',
      'SELECT "t".* FROM "user__roles" "t" WHERE ("entity_id" IN (2)) AND ("deleted" = 0) AND ("langcode" IN ("en", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "user__user_picture" "t" WHERE ("entity_id" IN (2)) AND ("deleted" = 0) AND ("langcode" IN ("en", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "theme:stark" ) AND "collection" = "config.entity.key_store.block"',
    ];
    $recorded_queries = $performance_data->getQueries();
    $this->assertSame($expected_queries, $recorded_queries);
    $expected = [
      'ScriptBytes' => 6500,
      'ScriptCount' => 1,
      'StylesheetBytes' => 1429,
      'StylesheetCount' => 1,
      'QueryCount' => 17,
      'CacheGetCount' => 68,
      'CacheSetCount' => 1,
      'CacheDeleteCount' => 1,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 13,
      'CacheTagGroupedLookups' => [
        // Form submission and login.
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
        // The user page after the redirect.
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
        ['rendered', 'user:2', 'user_view'],
        [
          'block_view',
          'config:block.block.stark_site_branding',
          'config:system.site',
        ],
        [
          'CACHE_MISS_IF_UNCACHEABLE_HTTP_METHOD:form',
          'config:block.block.stark_search_form_narrow',
          'config:search.settings',
        ],
        ['config:system.menu.account', 'config:system.menu.main'],
        ['config:block.block.stark_search_form_wide'],
        ['config:block.block.stark_messages'],
        ['config:block.block.stark_help'],
        ['config:block.block.stark_powered'],
        ['config:block.block.stark_main_menu'],
        [
          'config:block.block.stark_account_menu',
          'config:block.block.stark_breadcrumbs',
          'config:block.block.stark_primary_admin_actions',
        ],
        [
          'config:block.block.stark_primary_local_tasks',
          'config:user.role.authenticated',
          'config:block.block.stark_secondary_local_tasks',
        ],
      ],
    ];
    $this->assertMetrics($expected, $performance_data);
    $this->drupalLogout();
  }

  /**
   * Tests the performance of logging in via the user login block.
   */
  protected function testLoginBlock(): void {
    $this->drupalPlaceBlock('user_login_block');
    // Log the user in in to warm all caches. Manually submit the form so that
    // we repeat the same steps when recording performance data. Do this twice
    // so that any caches which take two requests to warm are also covered.

    for ($i = 0; $i < 2; $i++) {
      $this->drupalGet('node');
      $this->assertSession()->responseContains('Password');
      $this->submitLoginForm($this->user);
      $this->drupalLogout();
    }

    $this->drupalGet('node');
    $this->assertSession()->responseContains('Password');
    $performance_data = $this->collectPerformanceData(function () {
      $this->submitLoginForm($this->user);
    }, 'standardBlockLogin');

    $expected_queries = [
      'SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "theme:stark" ) AND "collection" = "config.entity.key_store.block"',
      'SELECT "config"."name" AS "name" FROM "config" "config" WHERE ("collection" = "") AND ("name" LIKE "search.page.%" ESCAPE ' . "'\\\\'" . ') ORDER BY "collection" ASC, "name" ASC',
      'SELECT "name", "value" FROM "key_value_expire" WHERE "expire" > "NOW" AND "name" IN ( "KEY" ) AND "collection" = "form"',
      'SELECT COUNT(*) AS "expression" FROM (SELECT 1 AS "expression" FROM "flood" "f" WHERE ("event" = "user.failed_login_ip") AND ("identifier" = "CLIENT_IP") AND ("timestamp" > "TIMESTAMP")) "subquery"',
      'SELECT "base_table"."uid" AS "uid", "base_table"."uid" AS "base_table_uid" FROM "users" "base_table" INNER JOIN "users_field_data" "users_field_data" ON "users_field_data"."uid" = "base_table"."uid" WHERE ("users_field_data"."name" IN ("ACCOUNT_NAME")) AND ("users_field_data"."default_langcode" IN (1))',
      'SELECT "base"."uid" AS "uid", "base"."uuid" AS "uuid", "base"."langcode" AS "langcode" FROM "users" "base" WHERE "base"."uid" IN (2)',
      'SELECT "data".* FROM "users_field_data" "data" WHERE "data"."uid" IN (2) ORDER BY "data"."uid" ASC',
      'SELECT "t".* FROM "user__roles" "t" WHERE ("entity_id" IN (2)) AND ("deleted" = 0) AND ("langcode" IN ("en", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "user__user_picture" "t" WHERE ("entity_id" IN (2)) AND ("deleted" = 0) AND ("langcode" IN ("en", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT COUNT(*) AS "expression" FROM (SELECT 1 AS "expression" FROM "flood" "f" WHERE ("event" = "user.failed_login_user") AND ("identifier" = "CLIENT_IP") AND ("timestamp" > "TIMESTAMP")) "subquery"',
      'INSERT INTO "watchdog" ("uid", "type", "message", "variables", "severity", "link", "location", "referer", "hostname", "timestamp") VALUES ("2", "user", "Session opened for %name.", "WATCHDOG_DATA", 6, "", "LOCATION", "REFERER", "CLIENT_IP", "TIMESTAMP")',
      'UPDATE "users_field_data" SET "login"="TIMESTAMP" WHERE "uid" = "2"',
      'SELECT "session" FROM "sessions" WHERE "sid" = "SESSION_ID" LIMIT 0, 1',
      'SELECT 1 AS "expression" FROM "sessions" "sessions" WHERE "sid" = "SESSION_ID"',
      'INSERT INTO "sessions" ("sid", "uid", "hostname", "session", "timestamp") VALUES ("SESSION_ID", "2", "CLIENT_IP", "SESSION_DATA", "TIMESTAMP")',
      'SELECT "session" FROM "sessions" WHERE "sid" = "SESSION_ID" LIMIT 0, 1',
      'SELECT * FROM "users_field_data" "u" WHERE "u"."uid" = "2" AND "u"."default_langcode" = 1',
      'SELECT "roles_target_id" FROM "user__roles" WHERE "entity_id" = "2"',
    ];
    $recorded_queries = $performance_data->getQueries();
    $this->assertSame($expected_queries, $recorded_queries);
    $expected = [
      'QueryCount' => 18,
      'CacheGetCount' => 103,
      'CacheSetCount' => 1,
      'CacheDeleteCount' => 1,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 20,
    ];
    $this->assertMetrics($expected, $performance_data);
  }

  /**
   * Submit the user login form.
   */
  protected function submitLoginForm($account): void {
    $this->submitForm([
      'name' => $account->getAccountName(),
      'pass' => $account->passRaw,
    ], 'Log in');
  }

  /**
   * Passes if no JavaScript is found on the page.
   *
   * @param Drupal\Tests\PerformanceData $performance_data
   *   A PerformanceData value object.
   *
   * @internal
   */
  protected function assertNoJavaScript(PerformanceData $performance_data): void {
    // Ensure drupalSettings is not set.
    $settings = $this->getDrupalSettings();
    $this->assertEmpty($settings, 'drupalSettings is not set.');
    $this->assertSession()->responseNotMatches('/\.js/');
    $this->assertSame(0, $performance_data->getScriptCount());
  }

  /**
   * Provides an empty implementation to prevent the resetting of caches.
   */
  protected function refreshVariables() {}

}

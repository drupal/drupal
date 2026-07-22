<?php

declare(strict_types=1);

namespace Drupal\Tests\standard\FunctionalJavascript;

use Drupal\Core\Cache\Cache;
use Drupal\FunctionalJavascriptTests\PerformanceTestBase;
use Drupal\Tests\PerformanceData;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cSpell:ignore mlid
/**
 * Tests the performance of basic functionality in the standard profile.
 *
 * Stark is used as the default theme so that this test is not Olivero specific.
 */
#[Group('Common')]
#[Group('#slow')]
#[RequiresPhpExtension('apcu')]
#[RunTestsInSeparateProcesses]
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
    // Create a content type and node to test performance for.
    $this->drupalCreateContentType(['type' => 'test_content', 'name' => 'Test Content']);
    $this->drupalCreateNode(['type' => 'test_content']);
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
    $this->testAdmin();
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
    $this->drupalGet('user/password');
    // Ensure everything finishes before we collect performance data.
    sleep(2);

    // Test frontpage.
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('');
    }, 'standardFrontPage');
    $this->assertNoJavaScript($performance_data);

    $expected_queries = [
      'SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."alias" LIKE "/user/login" ESCAPE ' . "'\\\\'" . ') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC',
      'SELECT "name", "route", "fit" FROM "router" WHERE "pattern_outline" IN ( "/user/login", "/user/%", "/user" ) AND "number_parts" >= 2',
      'SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "theme:stark" ) AND "collection" = "config.entity.key_store.block"',
      'SELECT "menu_tree"."menu_name" AS "menu_name", "menu_tree"."route_name" AS "route_name", "menu_tree"."route_parameters" AS "route_parameters", "menu_tree"."url" AS "url", "menu_tree"."title" AS "title", "menu_tree"."description" AS "description", "menu_tree"."parent" AS "parent", "menu_tree"."weight" AS "weight", "menu_tree"."options" AS "options", "menu_tree"."expanded" AS "expanded", "menu_tree"."enabled" AS "enabled", "menu_tree"."provider" AS "provider", "menu_tree"."metadata" AS "metadata", "menu_tree"."class" AS "class", "menu_tree"."form_class" AS "form_class", "menu_tree"."id" AS "id" FROM "menu_tree" "menu_tree" WHERE ("route_name" = "user.login") AND ("route_param_key" = "") AND ("menu_name" = "main") ORDER BY "depth" ASC, "weight" ASC, "id" ASC',
      'SELECT "menu_tree"."menu_name" AS "menu_name", "menu_tree"."route_name" AS "route_name", "menu_tree"."route_parameters" AS "route_parameters", "menu_tree"."url" AS "url", "menu_tree"."title" AS "title", "menu_tree"."description" AS "description", "menu_tree"."parent" AS "parent", "menu_tree"."weight" AS "weight", "menu_tree"."options" AS "options", "menu_tree"."expanded" AS "expanded", "menu_tree"."enabled" AS "enabled", "menu_tree"."provider" AS "provider", "menu_tree"."metadata" AS "metadata", "menu_tree"."class" AS "class", "menu_tree"."form_class" AS "form_class", "menu_tree"."id" AS "id" FROM "menu_tree" "menu_tree" WHERE ("route_name" = "<front>") AND ("route_param_key" = "") AND ("menu_name" = "main") ORDER BY "depth" ASC, "weight" ASC, "id" ASC',
      'SELECT "menu_tree"."p1" AS "p1", "menu_tree"."p2" AS "p2", "menu_tree"."p3" AS "p3", "menu_tree"."p4" AS "p4", "menu_tree"."p5" AS "p5", "menu_tree"."p6" AS "p6", "menu_tree"."p7" AS "p7", "menu_tree"."p8" AS "p8", "menu_tree"."p9" AS "p9" FROM "menu_tree" "menu_tree" WHERE "id" = "standard.front_page"',
      'SELECT "menu_tree"."id" AS "id" FROM "menu_tree" "menu_tree" WHERE "mlid" IN ("5") ORDER BY "depth" DESC',
      'SELECT "menu_tree"."menu_name" AS "menu_name", "menu_tree"."route_name" AS "route_name", "menu_tree"."route_parameters" AS "route_parameters", "menu_tree"."url" AS "url", "menu_tree"."title" AS "title", "menu_tree"."description" AS "description", "menu_tree"."parent" AS "parent", "menu_tree"."weight" AS "weight", "menu_tree"."options" AS "options", "menu_tree"."expanded" AS "expanded", "menu_tree"."enabled" AS "enabled", "menu_tree"."provider" AS "provider", "menu_tree"."metadata" AS "metadata", "menu_tree"."class" AS "class", "menu_tree"."form_class" AS "form_class", "menu_tree"."id" AS "id" FROM "menu_tree" "menu_tree" WHERE ("route_name" = "user.login") AND ("route_param_key" = "") AND ("menu_name" = "account") ORDER BY "depth" ASC, "weight" ASC, "id" ASC',
      'SELECT "menu_tree"."menu_name" AS "menu_name", "menu_tree"."route_name" AS "route_name", "menu_tree"."route_parameters" AS "route_parameters", "menu_tree"."url" AS "url", "menu_tree"."title" AS "title", "menu_tree"."description" AS "description", "menu_tree"."parent" AS "parent", "menu_tree"."weight" AS "weight", "menu_tree"."options" AS "options", "menu_tree"."expanded" AS "expanded", "menu_tree"."enabled" AS "enabled", "menu_tree"."provider" AS "provider", "menu_tree"."metadata" AS "metadata", "menu_tree"."class" AS "class", "menu_tree"."form_class" AS "form_class", "menu_tree"."id" AS "id" FROM "menu_tree" "menu_tree" WHERE ("route_name" = "<front>") AND ("route_param_key" = "") AND ("menu_name" = "account") ORDER BY "depth" ASC, "weight" ASC, "id" ASC',
      'SELECT "menu_tree".* FROM "menu_tree" "menu_tree" WHERE ("menu_name" = "main") AND ("depth" <= 2) ORDER BY "p1" ASC, "p2" ASC, "p3" ASC, "p4" ASC, "p5" ASC, "p6" ASC, "p7" ASC, "p8" ASC, "p9" ASC',
    ];
    $recorded_queries = $performance_data->getQueries();
    $this->assertSame($expected_queries, $recorded_queries);
    $expected = [
      'QueryCount' => 10,
      'CacheGetCount' => 43,
      'CacheGetCountByBin' => [
        'page' => 1,
        'config' => 10,
        'data' => 3,
        'bootstrap' => 8,
        'dynamic_page_cache' => 1,
        'discovery' => 10,
        'routes' => 3,
        'menu' => 3,
        'render' => 4,
      ],
      'CacheSetCount' => 13,
      'CacheDeleteCount' => 0,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 7,
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
        ['config:block_list', 'rendered'],
        ['CACHE_MISS_IF_UNCACHEABLE_HTTP_METHOD:form', 'config:system.site'],
        ['config:system.menu.account'],
        ['config:user.settings'],
        ['config:system.menu.main'],
        ['config:user.role.anonymous'],
      ],
      'StylesheetCount' => 1,
      'StylesheetBytes' => 1450,
    ];
    $this->assertMetrics($expected, $performance_data);

    // Test node page.
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'standardNodePage');
    $this->assertNoJavaScript($performance_data);

    $expected_queries = [
      'SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."alias" LIKE "/node/1" ESCAPE ' . "'\\\\'" . ') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC',
      'SELECT "name", "route", "fit" FROM "router" WHERE "pattern_outline" IN ( "/node/1", "/node/%", "/node" ) AND "number_parts" >= 2',
      'SELECT "revision"."vid" AS "vid", "revision"."langcode" AS "langcode", "revision"."revision_uid" AS "revision_uid", "revision"."revision_timestamp" AS "revision_timestamp", "revision"."revision_log" AS "revision_log", "revision"."revision_default" AS "revision_default", "base"."nid" AS "nid", "base"."type" AS "type", "base"."uuid" AS "uuid", CASE "base"."vid" WHEN "revision"."vid" THEN 1 ELSE 0 END AS "isDefaultRevision" FROM "node" "base" INNER JOIN "node_revision" "revision" ON "revision"."vid" = "base"."vid" WHERE "base"."nid" IN (1)',
      'SELECT "node_field_data".*, "node_field_data"."langcode" AS "node_field_data__langcode", "node__body"."body_value" AS "body_value", "node__body"."body_format" AS "body_format" FROM "node_field_data" "node_field_data" LEFT OUTER JOIN "node__body" "node__body" ON "node__body"."entity_id" = "node_field_data"."nid" AND "node__body"."langcode" = "node_field_data"."langcode" AND "node__body"."deleted" = 0 WHERE "node_field_data"."nid" IN (1)',
      'SELECT "name", "route" FROM "router" WHERE "name" IN ( "entity.node.canonical" )',
      'SELECT 1 AS "expression" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."path" LIKE "/node%" ESCAPE ' . "'\\\\'" . ') LIMIT 1 OFFSET 0',
      'SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "core.entity_view_display.node.test_content.full" )',
      'SELECT "config"."name" AS "name" FROM "config" "config" WHERE ("collection" = "") AND ("name" LIKE "node.type.%" ESCAPE ' . "'\\\\'" . ') ORDER BY "collection" ASC, "name" ASC',
      'SELECT "base"."uid" AS "uid", "base"."uuid" AS "uuid", "base"."langcode" AS "langcode" FROM "users" "base" WHERE "base"."uid" IN (0)',
      'SELECT "users_field_data".*, "users_field_data"."langcode" AS "users_field_data__langcode", "user__user_picture"."user_picture_target_id" AS "user_picture_target_id", "user__user_picture"."user_picture_alt" AS "user_picture_alt", "user__user_picture"."user_picture_title" AS "user_picture_title", "user__user_picture"."user_picture_width" AS "user_picture_width", "user__user_picture"."user_picture_height" AS "user_picture_height" FROM "users_field_data" "users_field_data" LEFT OUTER JOIN "user__user_picture" "user__user_picture" ON "user__user_picture"."entity_id" = "users_field_data"."uid" AND "user__user_picture"."langcode" = "users_field_data"."langcode" AND "user__user_picture"."deleted" = 0 WHERE "users_field_data"."uid" IN (0)',
      'SELECT "users_field_data".*, "users_field_data"."langcode" AS "users_field_data__langcode", "user__roles"."roles_target_id" AS "roles_target_id", "user__roles"."delta" AS "roles_delta" FROM "users_field_data" "users_field_data" INNER JOIN "user__roles" "user__roles" ON "user__roles"."entity_id" = "users_field_data"."uid" AND "user__roles"."langcode" = "users_field_data"."langcode" AND "user__roles"."deleted" = 0 WHERE "users_field_data"."uid" IN (0)',
      'SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "core.date_format.medium" )',
      'SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "core.date_format.long" )',
      'SELECT "name", "data" FROM "config" WHERE "collection" = "" AND "name" IN ( "filter.format.restricted_html" )',
      'SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "theme:stark" ) AND "collection" = "config.entity.key_store.block"',
      'SELECT "menu_tree"."menu_name" AS "menu_name", "menu_tree"."route_name" AS "route_name", "menu_tree"."route_parameters" AS "route_parameters", "menu_tree"."url" AS "url", "menu_tree"."title" AS "title", "menu_tree"."description" AS "description", "menu_tree"."parent" AS "parent", "menu_tree"."weight" AS "weight", "menu_tree"."options" AS "options", "menu_tree"."expanded" AS "expanded", "menu_tree"."enabled" AS "enabled", "menu_tree"."provider" AS "provider", "menu_tree"."metadata" AS "metadata", "menu_tree"."class" AS "class", "menu_tree"."form_class" AS "form_class", "menu_tree"."id" AS "id" FROM "menu_tree" "menu_tree" WHERE ("route_name" = "entity.node.canonical") AND ("route_param_key" = "node=1") AND ("menu_name" = "main") ORDER BY "depth" ASC, "weight" ASC, "id" ASC',
      'SELECT "menu_tree"."menu_name" AS "menu_name", "menu_tree"."route_name" AS "route_name", "menu_tree"."route_parameters" AS "route_parameters", "menu_tree"."url" AS "url", "menu_tree"."title" AS "title", "menu_tree"."description" AS "description", "menu_tree"."parent" AS "parent", "menu_tree"."weight" AS "weight", "menu_tree"."options" AS "options", "menu_tree"."expanded" AS "expanded", "menu_tree"."enabled" AS "enabled", "menu_tree"."provider" AS "provider", "menu_tree"."metadata" AS "metadata", "menu_tree"."class" AS "class", "menu_tree"."form_class" AS "form_class", "menu_tree"."id" AS "id" FROM "menu_tree" "menu_tree" WHERE ("route_name" = "entity.node.canonical") AND ("route_param_key" = "node=1") AND ("menu_name" = "account") ORDER BY "depth" ASC, "weight" ASC, "id" ASC',
      'SELECT "name", "route" FROM "router" WHERE "name" IN ( "layout_builder.overrides.node.view", "entity.node.edit_form", "entity.node.delete_form", "entity.node.version_history" )',
      'SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node_revision" "base_table" INNER JOIN (SELECT "subquery_base_table"."nid" AS "nid", MAX(subquery_base_table.vid) AS "maximum_revision_id" FROM "node_revision" "subquery_base_table" WHERE "nid" = "1" GROUP BY "subquery_base_table"."nid") "sq_base_table" ON base_table.nid = sq_base_table.nid AND base_table.vid = sq_base_table.maximum_revision_id INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE "node_field_data"."nid" = "1"',
      'SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."alias" LIKE "/node" ESCAPE ' . "'\\\\'" . ') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC',
      'SELECT "name", "route", "fit" FROM "router" WHERE "pattern_outline" IN ( "/node" ) AND "number_parts" >= 1',
      'INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("theme_registry:runtime:stark:Drupal\Core\Cache\CacheCollector", "LOCK_ID", "EXPIRE")',
      'DELETE FROM "semaphore"  WHERE ("name" = "theme_registry:runtime:stark:Drupal\Core\Cache\CacheCollector") AND ("value" = "LOCK_ID")',
      'INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("path_alias_prefix_list:Drupal\Core\Cache\CacheCollector", "LOCK_ID", "EXPIRE")',
      'DELETE FROM "semaphore"  WHERE ("name" = "path_alias_prefix_list:Drupal\Core\Cache\CacheCollector") AND ("value" = "LOCK_ID")',
    ];

    $recorded_queries = $performance_data->getQueries();
    $this->assertSame($expected_queries, $recorded_queries);
    $expected = [
      'QueryCount' => 25,
      'CacheGetCount' => 73,
      'CacheSetCount' => 42,
      'CacheDeleteCount' => 0,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 7,
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
        ['rendered', 'user:0', 'user_view'],
        ['config:filter.format.restricted_html', 'node:1', 'node_view'],
        ['config:block_list'],
        [
          'config:system.menu.main',
          'config:system.site',
        ],
        ['config:system.menu.account'],
        ['config:user.role.anonymous'],
      ],
      'StylesheetCount' => 1,
      'StylesheetBytes' => 1000,
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
      'SELECT "users_field_data".*, "users_field_data"."langcode" AS "users_field_data__langcode", "user__user_picture"."user_picture_target_id" AS "user_picture_target_id", "user__user_picture"."user_picture_alt" AS "user_picture_alt", "user__user_picture"."user_picture_title" AS "user_picture_title", "user__user_picture"."user_picture_width" AS "user_picture_width", "user__user_picture"."user_picture_height" AS "user_picture_height" FROM "users_field_data" "users_field_data" LEFT OUTER JOIN "user__user_picture" "user__user_picture" ON "user__user_picture"."entity_id" = "users_field_data"."uid" AND "user__user_picture"."langcode" = "users_field_data"."langcode" AND "user__user_picture"."deleted" = 0 WHERE "users_field_data"."uid" IN (2)',
      'SELECT "users_field_data".*, "users_field_data"."langcode" AS "users_field_data__langcode", "user__roles"."roles_target_id" AS "roles_target_id", "user__roles"."delta" AS "roles_delta" FROM "users_field_data" "users_field_data" INNER JOIN "user__roles" "user__roles" ON "user__roles"."entity_id" = "users_field_data"."uid" AND "user__roles"."langcode" = "users_field_data"."langcode" AND "user__roles"."deleted" = 0 WHERE "users_field_data"."uid" IN (2)',
      'SELECT "name", "route" FROM "router" WHERE "name" IN ( "entity.user.canonical" )',
      'SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "theme:stark" ) AND "collection" = "config.entity.key_store.block"',
      'SELECT "menu_tree"."menu_name" AS "menu_name", "menu_tree"."route_name" AS "route_name", "menu_tree"."route_parameters" AS "route_parameters", "menu_tree"."url" AS "url", "menu_tree"."title" AS "title", "menu_tree"."description" AS "description", "menu_tree"."parent" AS "parent", "menu_tree"."weight" AS "weight", "menu_tree"."options" AS "options", "menu_tree"."expanded" AS "expanded", "menu_tree"."enabled" AS "enabled", "menu_tree"."provider" AS "provider", "menu_tree"."metadata" AS "metadata", "menu_tree"."class" AS "class", "menu_tree"."form_class" AS "form_class", "menu_tree"."id" AS "id" FROM "menu_tree" "menu_tree" WHERE ("route_name" = "entity.user.canonical") AND ("route_param_key" = "user=2") AND ("menu_name" = "main") ORDER BY "depth" ASC, "weight" ASC, "id" ASC',
      'SELECT "menu_tree"."menu_name" AS "menu_name", "menu_tree"."route_name" AS "route_name", "menu_tree"."route_parameters" AS "route_parameters", "menu_tree"."url" AS "url", "menu_tree"."title" AS "title", "menu_tree"."description" AS "description", "menu_tree"."parent" AS "parent", "menu_tree"."weight" AS "weight", "menu_tree"."options" AS "options", "menu_tree"."expanded" AS "expanded", "menu_tree"."enabled" AS "enabled", "menu_tree"."provider" AS "provider", "menu_tree"."metadata" AS "metadata", "menu_tree"."class" AS "class", "menu_tree"."form_class" AS "form_class", "menu_tree"."id" AS "id" FROM "menu_tree" "menu_tree" WHERE ("route_name" = "entity.user.canonical") AND ("route_param_key" = "user=2") AND ("menu_name" = "account") ORDER BY "depth" ASC, "weight" ASC, "id" ASC',
      'SELECT "name", "route" FROM "router" WHERE "name" IN ( "layout_builder.overrides.user.view", "entity.user.edit_form" )',
    ];
    $recorded_queries = $performance_data->getQueries();
    $this->assertSame($expected_queries, $recorded_queries);
    $expected = [
      'QueryCount' => 10,
      'CacheGetCount' => 56,
      'CacheSetCount' => 17,
      'CacheDeleteCount' => 0,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 6,
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
      $this->drupalGet('');
      $this->submitLoginForm($this->user);
      $this->drupalLogout();
    }

    $this->drupalGet('');
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
      'INSERT INTO "sessions" ("sid", "uid", "hostname", "session", "timestamp") VALUES ("SESSION_ID", "2", "CLIENT_IP", "SESSION_DATA", "TIMESTAMP") ON DUPLICATE KEY UPDATE "uid" = VALUES("uid"), "hostname" = VALUES("hostname"), "session" = VALUES("session"), "timestamp" = VALUES("timestamp")',
      'SELECT "session" FROM "sessions" WHERE "sid" = "SESSION_ID" LIMIT 0, 1',
      'SELECT * FROM "users_field_data" "u" WHERE "u"."uid" = "2" AND "u"."default_langcode" = 1',
      'SELECT "roles_target_id" FROM "user__roles" WHERE "entity_id" = "2"',
      'SELECT "base"."uid" AS "uid", "base"."uuid" AS "uuid", "base"."langcode" AS "langcode" FROM "users" "base" WHERE "base"."uid" IN (2)',
      'SELECT "users_field_data".*, "users_field_data"."langcode" AS "users_field_data__langcode", "user__user_picture"."user_picture_target_id" AS "user_picture_target_id", "user__user_picture"."user_picture_alt" AS "user_picture_alt", "user__user_picture"."user_picture_title" AS "user_picture_title", "user__user_picture"."user_picture_width" AS "user_picture_width", "user__user_picture"."user_picture_height" AS "user_picture_height" FROM "users_field_data" "users_field_data" LEFT OUTER JOIN "user__user_picture" "user__user_picture" ON "user__user_picture"."entity_id" = "users_field_data"."uid" AND "user__user_picture"."langcode" = "users_field_data"."langcode" AND "user__user_picture"."deleted" = 0 WHERE "users_field_data"."uid" IN (2)',
      'SELECT "users_field_data".*, "users_field_data"."langcode" AS "users_field_data__langcode", "user__roles"."roles_target_id" AS "roles_target_id", "user__roles"."delta" AS "roles_delta" FROM "users_field_data" "users_field_data" INNER JOIN "user__roles" "user__roles" ON "user__roles"."entity_id" = "users_field_data"."uid" AND "user__roles"."langcode" = "users_field_data"."langcode" AND "user__roles"."deleted" = 0 WHERE "users_field_data"."uid" IN (2)',
      'SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "theme:stark" ) AND "collection" = "config.entity.key_store.block"',
    ];
    $recorded_queries = $performance_data->getQueries();
    $this->assertSame($expected_queries, $recorded_queries);
    $expected = [
      'ScriptBytes' => 6500,
      'ScriptCount' => 2,
      'StylesheetBytes' => 1429,
      'StylesheetCount' => 1,
      'QueryCount' => 15,
      'CacheGetCount' => 71,
      'CacheSetCount' => 1,
      'CacheDeleteCount' => 1,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 7,
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
          'http_response',
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
          'http_response',
        ],
        ['rendered', 'user:2', 'user_view'],
        [
          'config:system.menu.account',
          'config:system.menu.main',
        ],
        ['config:block_list'],
        ['config:system.site'],
        ['config:user.role.authenticated'],
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
      $this->drupalGet('node/1');
      $this->assertSession()->responseContains('Password');
      $this->submitLoginForm($this->user);
      $this->drupalLogout();
    }

    $this->drupalGet('node/1');
    $this->assertSession()->responseContains('Password');
    $performance_data = $this->collectPerformanceData(function () {
      $this->submitLoginForm($this->user);
    }, 'standardBlockLogin');

    $expected_queries = [
      'SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "theme:stark" ) AND "collection" = "config.entity.key_store.block"',
      'SELECT "name", "value" FROM "key_value_expire" WHERE "expire" > "NOW" AND "name" IN ( "KEY" ) AND "collection" = "form"',
      'SELECT COUNT(*) AS "expression" FROM (SELECT 1 AS "expression" FROM "flood" "f" WHERE ("event" = "user.failed_login_ip") AND ("identifier" = "CLIENT_IP") AND ("timestamp" > "TIMESTAMP")) "subquery"',
      'SELECT "base_table"."uid" AS "uid", "base_table"."uid" AS "base_table_uid" FROM "users" "base_table" INNER JOIN "users_field_data" "users_field_data" ON "users_field_data"."uid" = "base_table"."uid" WHERE ("users_field_data"."name" IN ("ACCOUNT_NAME")) AND ("users_field_data"."default_langcode" IN (1))',
      'SELECT "base"."uid" AS "uid", "base"."uuid" AS "uuid", "base"."langcode" AS "langcode" FROM "users" "base" WHERE "base"."uid" IN (2)',
      'SELECT "users_field_data".*, "users_field_data"."langcode" AS "users_field_data__langcode", "user__user_picture"."user_picture_target_id" AS "user_picture_target_id", "user__user_picture"."user_picture_alt" AS "user_picture_alt", "user__user_picture"."user_picture_title" AS "user_picture_title", "user__user_picture"."user_picture_width" AS "user_picture_width", "user__user_picture"."user_picture_height" AS "user_picture_height" FROM "users_field_data" "users_field_data" LEFT OUTER JOIN "user__user_picture" "user__user_picture" ON "user__user_picture"."entity_id" = "users_field_data"."uid" AND "user__user_picture"."langcode" = "users_field_data"."langcode" AND "user__user_picture"."deleted" = 0 WHERE "users_field_data"."uid" IN (2)',
      'SELECT "users_field_data".*, "users_field_data"."langcode" AS "users_field_data__langcode", "user__roles"."roles_target_id" AS "roles_target_id", "user__roles"."delta" AS "roles_delta" FROM "users_field_data" "users_field_data" INNER JOIN "user__roles" "user__roles" ON "user__roles"."entity_id" = "users_field_data"."uid" AND "user__roles"."langcode" = "users_field_data"."langcode" AND "user__roles"."deleted" = 0 WHERE "users_field_data"."uid" IN (2)',
      'SELECT COUNT(*) AS "expression" FROM (SELECT 1 AS "expression" FROM "flood" "f" WHERE ("event" = "user.failed_login_user") AND ("identifier" = "CLIENT_IP") AND ("timestamp" > "TIMESTAMP")) "subquery"',
      'INSERT INTO "watchdog" ("uid", "type", "message", "variables", "severity", "link", "location", "referer", "hostname", "timestamp") VALUES ("2", "user", "Session opened for %name.", "WATCHDOG_DATA", 6, "", "LOCATION", "REFERER", "CLIENT_IP", "TIMESTAMP")',
      'UPDATE "users_field_data" SET "login"="TIMESTAMP" WHERE "uid" = "2"',
      'SELECT "session" FROM "sessions" WHERE "sid" = "SESSION_ID" LIMIT 0, 1',
      'INSERT INTO "sessions" ("sid", "uid", "hostname", "session", "timestamp") VALUES ("SESSION_ID", "2", "CLIENT_IP", "SESSION_DATA", "TIMESTAMP") ON DUPLICATE KEY UPDATE "uid" = VALUES("uid"), "hostname" = VALUES("hostname"), "session" = VALUES("session"), "timestamp" = VALUES("timestamp")',
      'SELECT "session" FROM "sessions" WHERE "sid" = "SESSION_ID" LIMIT 0, 1',
      'SELECT * FROM "users_field_data" "u" WHERE "u"."uid" = "2" AND "u"."default_langcode" = 1',
      'SELECT "roles_target_id" FROM "user__roles" WHERE "entity_id" = "2"',
    ];
    $recorded_queries = $performance_data->getQueries();
    $this->assertSame($expected_queries, $recorded_queries);
    $expected = [
      'QueryCount' => 15,
      'CacheGetCount' => 98,
      'CacheSetCount' => 1,
      'CacheDeleteCount' => 1,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 9,
    ];
    $this->assertMetrics($expected, $performance_data);
  }

  /**
   * Tests performance of a logged-in admin user with the navigation toolbar.
   */
  protected function testAdmin(): void {
    $admin_user = $this->drupalCreateUser();
    $admin_user->addRole('administrator');
    $admin_user->save();

    // Ensure no user is logged in and clear the render cache bin before
    // starting the warm-up, since prior sub-tests may leave an active session
    // and stale render cache entries.
    $this->drupalLogout();
    \Drupal::cache('render')->deleteAll();

    $this->drupalLogin($admin_user);
    // Request the node/1 page twice to ensure all cache collectors are fully
    // warmed. The exact contents of cache collectors depends on the order in
    // which requests complete so this ensures that the second request completes
    // after asset aggregates are served.
    $this->drupalGet('node/1');
    sleep(1);
    $this->drupalGet('node/1');
    // Flush the dynamic page cache to simulate visiting a page that is not
    // already fully cached.
    \Drupal::cache('dynamic_page_cache')->deleteAll();
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'testAdmin');

    $expected_queries = [
      'SELECT "session" FROM "sessions" WHERE "sid" = "SESSION_ID" LIMIT 0, 1',
      'SELECT * FROM "users_field_data" "u" WHERE "u"."uid" = "3" AND "u"."default_langcode" = 1',
      'SELECT "roles_target_id" FROM "user__roles" WHERE "entity_id" = "3"',
      'SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "theme:stark" ) AND "collection" = "config.entity.key_store.block"',
      'SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node_revision" "base_table" INNER JOIN (SELECT "subquery_base_table"."nid" AS "nid", MAX(subquery_base_table.vid) AS "maximum_revision_id" FROM "node_revision" "subquery_base_table" WHERE "nid" = "1" GROUP BY "subquery_base_table"."nid") "sq_base_table" ON base_table.nid = sq_base_table.nid AND base_table.vid = sq_base_table.maximum_revision_id INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE "node_field_data"."nid" = "1"',
    ];
    $recorded_queries = $performance_data->getQueries();
    $this->assertSame($expected_queries, $recorded_queries);

    $expected = [
      'QueryCount' => 5,
      'CacheGetCount' => 59,
      'CacheGetCountByBin' => [
        'config' => 11,
        'data' => 4,
        'discovery' => 19,
        'bootstrap' => 8,
        'entity' => 1,
        'dynamic_page_cache' => 1,
        'routes' => 5,
        'render' => 9,
        'menu' => 1,
      ],
      'CacheSetCount' => 2,
      'CacheSetCountByBin' => [
        'dynamic_page_cache' => 2,
      ],
      'CacheDeleteCount' => 0,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 7,
      'ScriptCount' => 9,
      'ScriptBytes' => 141736,
      'StylesheetCount' => 3,
      'StylesheetBytes' => 42533,
    ];
    $this->assertMetrics($expected, $performance_data);

    // The navigation toolbar must be cached under low-cardinality contexts,
    // not per-user, to ensure it scales for authenticated admins.
    $this->assertIsObject(\Drupal::cache('render')->get('navigation:navigation:[languages:language_interface]=en:[theme]=stark:[user.permissions]=is-admin'));
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
   * @param \Drupal\Tests\PerformanceData $performance_data
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

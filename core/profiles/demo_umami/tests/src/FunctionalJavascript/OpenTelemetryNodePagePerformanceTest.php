<?php

declare(strict_types=1);

namespace Drupal\Tests\demo_umami\FunctionalJavascript;

use Drupal\Core\Cache\Cache;
use Drupal\FunctionalJavascriptTests\PerformanceTestBase;

/**
 * Tests demo_umami profile performance.
 *
 * @group OpenTelemetry
 * @group #slow
 * @requires extension apcu
 */
class OpenTelemetryNodePagePerformanceTest extends PerformanceTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'demo_umami';

  /**
   * Test canonical node page performance with various cache permutations.
   */
  public function testNodePage(): void {
    $this->testNodePageColdCache();
    $this->testNodePageCoolCache();
    $this->testNodePageWarmCache();
    $this->testNodePageHotCache();
  }

  /**
   * Logs node page tracing data with a cold cache.
   */
  protected function testNodePageColdCache(): void {
    // @todo Chromedriver doesn't collect tracing performance logs for the very
    //   first request in a test, so warm it up.
    //   https://www.drupal.org/project/drupal/issues/3379750
    $this->drupalGet('user/login');
    $this->rebuildAll();
    $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'umamiNodePageColdCache');
    $this->assertSession()->pageTextContains('quiche');
  }

  /**
   * Logs node page tracing data with a hot cache.
   *
   * Hot here means that all possible caches are warmed.
   */
  protected function testNodePageHotCache(): void {
    // Request the page twice so that asset aggregates are definitely cached in
    // the browser cache.
    $this->drupalGet('node/1');
    $this->drupalGet('node/1');

    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'umamiNodePageHotCache');
    $this->assertSession()->pageTextContains('quiche');
    $this->assertSame($performance_data->getQueryCount(), 0);
    $this->assertSame($performance_data->getCacheGetCount(), 1);
    $this->assertSame($performance_data->getCacheSetCount(), 0);
    $this->assertSame($performance_data->getCacheDeleteCount(), 0);
    $this->assertSame(0, $performance_data->getCacheTagChecksumCount());
    $this->assertSame(1, $performance_data->getCacheTagIsValidCount());
  }

  /**
   * Logs node/1 tracing data with a cool cache.
   *
   * Cool here means that 'global' site caches are warm but anything
   * specific to the route or path is cold.
   */
  protected function testNodePageCoolCache(): void {
    // First of all visit the node page to ensure the image style exists.
    $this->drupalGet('node/1');
    $this->clearCaches();
    // Now visit a non-node page to warm non-route-specific caches.
    $this->drupalGet('user/login');
    $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'umamiNodePageCoolCache');
    $this->assertSession()->pageTextContains('quiche');
  }

  /**
   * Log node/1 tracing data with a warm cache.
   *
   * Warm here means that 'global' site caches and route-specific caches are
   * warm but caches specific to this particular node/path are not.
   */
  protected function testNodePageWarmCache(): void {
    // First of all visit the node page to ensure the image style exists.
    $this->drupalGet('node/1');
    $this->clearCaches();
    // Now visit a different node page to warm non-path-specific caches.
    $this->drupalGet('node/2');
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'umamiNodePageWarmCache');
    $this->assertSession()->pageTextContains('quiche');
    // Check the actual queries so that if a change simultaneously adds and
    // removes a query the change is detected.
    $expected_queries = [
      'SELECT "config"."name" AS "name" FROM "config" "config" WHERE ("collection" = "") AND ("name" LIKE "language.entity.%" ESCAPE \'\\\\\') ORDER BY "collection" ASC, "name" ASC',
      'SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."alias" LIKE "/recipes/deep-mediterranean-quiche" ESCAPE \'\\\\\') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC',
      'SELECT "name", "route", "fit" FROM "router" WHERE "pattern_outline" IN ( "/node/1", "/node/%", "/node" ) AND "number_parts" >= 2',
      'SELECT "revision"."vid" AS "vid", "revision"."langcode" AS "langcode", "revision"."revision_uid" AS "revision_uid", "revision"."revision_timestamp" AS "revision_timestamp", "revision"."revision_log" AS "revision_log", "revision"."revision_default" AS "revision_default", "base"."nid" AS "nid", "base"."type" AS "type", "base"."uuid" AS "uuid", CASE "base"."vid" WHEN "revision"."vid" THEN 1 ELSE 0 END AS "isDefaultRevision" FROM "node" "base" INNER JOIN "node_revision" "revision" ON "revision"."vid" = "base"."vid" WHERE "base"."nid" IN (1)',
      'SELECT "revision".* FROM "node_field_revision" "revision" WHERE ("revision"."nid" IN (1)) AND ("revision"."vid" IN ("76")) ORDER BY "revision"."nid" ASC',
      'SELECT "t".* FROM "node__field_cooking_time" "t" WHERE ("entity_id" IN (1)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node__field_difficulty" "t" WHERE ("entity_id" IN (1)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node__field_ingredients" "t" WHERE ("entity_id" IN (1)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node__field_media_image" "t" WHERE ("entity_id" IN (1)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node__field_number_of_servings" "t" WHERE ("entity_id" IN (1)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node__field_preparation_time" "t" WHERE ("entity_id" IN (1)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node__field_recipe_category" "t" WHERE ("entity_id" IN (1)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node__field_recipe_instruction" "t" WHERE ("entity_id" IN (1)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node__field_summary" "t" WHERE ("entity_id" IN (1)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node__field_tags" "t" WHERE ("entity_id" IN (1)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node__layout_builder__layout" "t" WHERE ("entity_id" IN (1)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."path" LIKE "/node/1" ESCAPE \'\\\\\') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC',
      'SELECT "revision"."revision_id" AS "revision_id", "revision"."langcode" AS "langcode", "revision"."revision_user" AS "revision_user", "revision"."revision_created" AS "revision_created", "revision"."revision_log_message" AS "revision_log_message", "revision"."revision_default" AS "revision_default", "base"."tid" AS "tid", "base"."vid" AS "vid", "base"."uuid" AS "uuid", CASE "base"."revision_id" WHEN "revision"."revision_id" THEN 1 ELSE 0 END AS "isDefaultRevision" FROM "taxonomy_term_data" "base" INNER JOIN "taxonomy_term_revision" "revision" ON "revision"."revision_id" = "base"."revision_id" WHERE "base"."tid" IN (31)',
      'SELECT "revision".*, "data"."weight" AS "weight" FROM "taxonomy_term_field_revision" "revision" LEFT OUTER JOIN "taxonomy_term_field_data" "data" ON ("revision"."tid" = "data"."tid" AND "revision"."langcode" = "data"."langcode") WHERE ("revision"."tid" IN (31)) AND ("revision"."revision_id" IN ("31")) ORDER BY "revision"."tid" ASC',
      'SELECT "t".* FROM "taxonomy_term__parent" "t" WHERE ("entity_id" IN (31)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "revision"."revision_id" AS "revision_id", "revision"."langcode" AS "langcode", "revision"."revision_user" AS "revision_user", "revision"."revision_created" AS "revision_created", "revision"."revision_log_message" AS "revision_log_message", "revision"."revision_default" AS "revision_default", "base"."tid" AS "tid", "base"."vid" AS "vid", "base"."uuid" AS "uuid", CASE "base"."revision_id" WHEN "revision"."revision_id" THEN 1 ELSE 0 END AS "isDefaultRevision" FROM "taxonomy_term_data" "base" INNER JOIN "taxonomy_term_revision" "revision" ON "revision"."revision_id" = "base"."revision_id" WHERE "base"."tid" IN (22)',
      'SELECT "revision".*, "data"."weight" AS "weight" FROM "taxonomy_term_field_revision" "revision" LEFT OUTER JOIN "taxonomy_term_field_data" "data" ON ("revision"."tid" = "data"."tid" AND "revision"."langcode" = "data"."langcode") WHERE ("revision"."tid" IN (22)) AND ("revision"."revision_id" IN ("22")) ORDER BY "revision"."tid" ASC',
      'SELECT "t".* FROM "taxonomy_term__parent" "t" WHERE ("entity_id" IN (22)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "revision"."vid" AS "vid", "revision"."langcode" AS "langcode", "revision"."revision_user" AS "revision_user", "revision"."revision_created" AS "revision_created", "revision"."revision_log_message" AS "revision_log_message", "revision"."revision_default" AS "revision_default", "base"."mid" AS "mid", "base"."bundle" AS "bundle", "base"."uuid" AS "uuid", CASE "base"."vid" WHEN "revision"."vid" THEN 1 ELSE 0 END AS "isDefaultRevision" FROM "media" "base" INNER JOIN "media_revision" "revision" ON "revision"."vid" = "base"."vid" WHERE "base"."mid" IN (1)',
      'SELECT "revision".* FROM "media_field_revision" "revision" WHERE ("revision"."mid" IN (1)) AND ("revision"."vid" IN ("1")) ORDER BY "revision"."mid" ASC',
      'SELECT "t".* FROM "media__field_media_image" "t" WHERE ("entity_id" IN (1)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "node_field_data"."created" AS "node_field_data_created", "node_field_data"."nid" AS "nid", "node_field_data"."langcode" AS "node_field_data_langcode" FROM "node_field_data" "node_field_data" LEFT JOIN "node__field_recipe_category" "node__field_recipe_category" ON node_field_data.nid = node__field_recipe_category.entity_id AND node__field_recipe_category.deleted = 0 WHERE (((node_field_data.nid != "1" OR node_field_data.nid IS NULL)) AND ((node__field_recipe_category.field_recipe_category_target_id IN("31", "22", "13")))) AND (("node_field_data"."status" = 1) AND ("node_field_data"."type" IN ("recipe")) AND ("node_field_data"."langcode" IN ("en"))) ORDER BY "node_field_data_created" DESC LIMIT 4 OFFSET 0',
      'SELECT "revision"."vid" AS "vid", "revision"."langcode" AS "langcode", "revision"."revision_uid" AS "revision_uid", "revision"."revision_timestamp" AS "revision_timestamp", "revision"."revision_log" AS "revision_log", "revision"."revision_default" AS "revision_default", "base"."nid" AS "nid", "base"."type" AS "type", "base"."uuid" AS "uuid", CASE "base"."vid" WHEN "revision"."vid" THEN 1 ELSE 0 END AS "isDefaultRevision" FROM "node" "base" INNER JOIN "node_revision" "revision" ON "revision"."vid" = "base"."vid" WHERE "base"."nid" IN (10, 7, 6, 3)',
      'SELECT "revision".* FROM "node_field_revision" "revision" WHERE ("revision"."nid" IN (3, 6, 7, 10)) AND ("revision"."vid" IN ("72", "66", "64", "58")) ORDER BY "revision"."nid" ASC',
      'SELECT "t".* FROM "node__field_cooking_time" "t" WHERE ("entity_id" IN (3, 6, 7, 10)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node__field_difficulty" "t" WHERE ("entity_id" IN (3, 6, 7, 10)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node__field_ingredients" "t" WHERE ("entity_id" IN (3, 6, 7, 10)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node__field_media_image" "t" WHERE ("entity_id" IN (3, 6, 7, 10)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node__field_number_of_servings" "t" WHERE ("entity_id" IN (3, 6, 7, 10)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node__field_preparation_time" "t" WHERE ("entity_id" IN (3, 6, 7, 10)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node__field_recipe_category" "t" WHERE ("entity_id" IN (3, 6, 7, 10)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node__field_recipe_instruction" "t" WHERE ("entity_id" IN (3, 6, 7, 10)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node__field_summary" "t" WHERE ("entity_id" IN (3, 6, 7, 10)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node__field_tags" "t" WHERE ("entity_id" IN (3, 6, 7, 10)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node__layout_builder__layout" "t" WHERE ("entity_id" IN (3, 6, 7, 10)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."path" LIKE "/taxonomy/term/31" ESCAPE \'\\\\\') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC',
      'SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."path" LIKE "/taxonomy/term/22" ESCAPE \'\\\\\') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC',
      'SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."path" LIKE "/taxonomy/term/13" ESCAPE \'\\\\\') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC',
      'SELECT "base"."fid" AS "fid", "base"."uuid" AS "uuid", "base"."langcode" AS "langcode", "base"."uid" AS "uid", "base"."filename" AS "filename", "base"."uri" AS "uri", "base"."filemime" AS "filemime", "base"."filesize" AS "filesize", "base"."status" AS "status", "base"."created" AS "created", "base"."changed" AS "changed" FROM "file_managed" "base" WHERE "base"."fid" IN (1)',
      'SELECT "revision"."vid" AS "vid", "revision"."langcode" AS "langcode", "revision"."revision_user" AS "revision_user", "revision"."revision_created" AS "revision_created", "revision"."revision_log_message" AS "revision_log_message", "revision"."revision_default" AS "revision_default", "base"."mid" AS "mid", "base"."bundle" AS "bundle", "base"."uuid" AS "uuid", CASE "base"."vid" WHEN "revision"."vid" THEN 1 ELSE 0 END AS "isDefaultRevision" FROM "media" "base" INNER JOIN "media_revision" "revision" ON "revision"."vid" = "base"."vid" WHERE "base"."mid" IN (21)',
      'SELECT "revision".* FROM "media_field_revision" "revision" WHERE ("revision"."mid" IN (21)) AND ("revision"."vid" IN ("21")) ORDER BY "revision"."mid" ASC',
      'SELECT "t".* FROM "media__field_media_image" "t" WHERE ("entity_id" IN (21)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."path" LIKE "/node/10" ESCAPE \'\\\\\') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC',
      'SELECT "base"."fid" AS "fid", "base"."uuid" AS "uuid", "base"."langcode" AS "langcode", "base"."uid" AS "uid", "base"."filename" AS "filename", "base"."uri" AS "uri", "base"."filemime" AS "filemime", "base"."filesize" AS "filesize", "base"."status" AS "status", "base"."created" AS "created", "base"."changed" AS "changed" FROM "file_managed" "base" WHERE "base"."fid" IN (41)',
      'SELECT "revision"."vid" AS "vid", "revision"."langcode" AS "langcode", "revision"."revision_user" AS "revision_user", "revision"."revision_created" AS "revision_created", "revision"."revision_log_message" AS "revision_log_message", "revision"."revision_default" AS "revision_default", "base"."mid" AS "mid", "base"."bundle" AS "bundle", "base"."uuid" AS "uuid", CASE "base"."vid" WHEN "revision"."vid" THEN 1 ELSE 0 END AS "isDefaultRevision" FROM "media" "base" INNER JOIN "media_revision" "revision" ON "revision"."vid" = "base"."vid" WHERE "base"."mid" IN (7)',
      'SELECT "revision".* FROM "media_field_revision" "revision" WHERE ("revision"."mid" IN (7)) AND ("revision"."vid" IN ("7")) ORDER BY "revision"."mid" ASC',
      'SELECT "t".* FROM "media__field_media_image" "t" WHERE ("entity_id" IN (7)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."path" LIKE "/node/7" ESCAPE \'\\\\\') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC',
      'SELECT "base"."fid" AS "fid", "base"."uuid" AS "uuid", "base"."langcode" AS "langcode", "base"."uid" AS "uid", "base"."filename" AS "filename", "base"."uri" AS "uri", "base"."filemime" AS "filemime", "base"."filesize" AS "filesize", "base"."status" AS "status", "base"."created" AS "created", "base"."changed" AS "changed" FROM "file_managed" "base" WHERE "base"."fid" IN (13)',
      'SELECT "revision"."vid" AS "vid", "revision"."langcode" AS "langcode", "revision"."revision_user" AS "revision_user", "revision"."revision_created" AS "revision_created", "revision"."revision_log_message" AS "revision_log_message", "revision"."revision_default" AS "revision_default", "base"."mid" AS "mid", "base"."bundle" AS "bundle", "base"."uuid" AS "uuid", CASE "base"."vid" WHEN "revision"."vid" THEN 1 ELSE 0 END AS "isDefaultRevision" FROM "media" "base" INNER JOIN "media_revision" "revision" ON "revision"."vid" = "base"."vid" WHERE "base"."mid" IN (6)',
      'SELECT "revision".* FROM "media_field_revision" "revision" WHERE ("revision"."mid" IN (6)) AND ("revision"."vid" IN ("6")) ORDER BY "revision"."mid" ASC',
      'SELECT "t".* FROM "media__field_media_image" "t" WHERE ("entity_id" IN (6)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."path" LIKE "/node/6" ESCAPE \'\\\\\') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC',
      'SELECT "base"."fid" AS "fid", "base"."uuid" AS "uuid", "base"."langcode" AS "langcode", "base"."uid" AS "uid", "base"."filename" AS "filename", "base"."uri" AS "uri", "base"."filemime" AS "filemime", "base"."filesize" AS "filesize", "base"."status" AS "status", "base"."created" AS "created", "base"."changed" AS "changed" FROM "file_managed" "base" WHERE "base"."fid" IN (11)',
      'SELECT "revision"."vid" AS "vid", "revision"."langcode" AS "langcode", "revision"."revision_user" AS "revision_user", "revision"."revision_created" AS "revision_created", "revision"."revision_log_message" AS "revision_log_message", "revision"."revision_default" AS "revision_default", "base"."mid" AS "mid", "base"."bundle" AS "bundle", "base"."uuid" AS "uuid", CASE "base"."vid" WHEN "revision"."vid" THEN 1 ELSE 0 END AS "isDefaultRevision" FROM "media" "base" INNER JOIN "media_revision" "revision" ON "revision"."vid" = "base"."vid" WHERE "base"."mid" IN (3)',
      'SELECT "revision".* FROM "media_field_revision" "revision" WHERE ("revision"."mid" IN (3)) AND ("revision"."vid" IN ("3")) ORDER BY "revision"."mid" ASC',
      'SELECT "t".* FROM "media__field_media_image" "t" WHERE ("entity_id" IN (3)) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."path" LIKE "/node/3" ESCAPE \'\\\\\') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC',
      'SELECT "base"."fid" AS "fid", "base"."uuid" AS "uuid", "base"."langcode" AS "langcode", "base"."uid" AS "uid", "base"."filename" AS "filename", "base"."uri" AS "uri", "base"."filemime" AS "filemime", "base"."filesize" AS "filesize", "base"."status" AS "status", "base"."created" AS "created", "base"."changed" AS "changed" FROM "file_managed" "base" WHERE "base"."fid" IN (5)',
      'SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "theme:umami" ) AND "collection" = "config.entity.key_store.block"',
      'SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."path" LIKE "/node/1" ESCAPE \'\\\\\') AND ("base_table"."langcode" IN ("es", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC',
      'SELECT "menu_tree"."menu_name" AS "menu_name", "menu_tree"."route_name" AS "route_name", "menu_tree"."route_parameters" AS "route_parameters", "menu_tree"."url" AS "url", "menu_tree"."title" AS "title", "menu_tree"."description" AS "description", "menu_tree"."parent" AS "parent", "menu_tree"."weight" AS "weight", "menu_tree"."options" AS "options", "menu_tree"."expanded" AS "expanded", "menu_tree"."enabled" AS "enabled", "menu_tree"."provider" AS "provider", "menu_tree"."metadata" AS "metadata", "menu_tree"."class" AS "class", "menu_tree"."form_class" AS "form_class", "menu_tree"."id" AS "id" FROM "menu_tree" "menu_tree" WHERE ("route_name" = "entity.node.canonical") AND ("route_param_key" = "node=1") AND ("menu_name" = "account") ORDER BY "depth" ASC, "weight" ASC, "id" ASC',
      'SELECT "menu_tree"."menu_name" AS "menu_name", "menu_tree"."route_name" AS "route_name", "menu_tree"."route_parameters" AS "route_parameters", "menu_tree"."url" AS "url", "menu_tree"."title" AS "title", "menu_tree"."description" AS "description", "menu_tree"."parent" AS "parent", "menu_tree"."weight" AS "weight", "menu_tree"."options" AS "options", "menu_tree"."expanded" AS "expanded", "menu_tree"."enabled" AS "enabled", "menu_tree"."provider" AS "provider", "menu_tree"."metadata" AS "metadata", "menu_tree"."class" AS "class", "menu_tree"."form_class" AS "form_class", "menu_tree"."id" AS "id" FROM "menu_tree" "menu_tree" WHERE ("route_name" = "entity.node.canonical") AND ("route_param_key" = "node=1") AND ("menu_name" = "main") ORDER BY "depth" ASC, "weight" ASC, "id" ASC',
      'SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node_revision" "base_table" INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" INNER JOIN "node_field_revision" "node_field_revision" ON "node_field_revision"."vid" = "base_table"."vid" AND "node_field_revision"."langcode" = "en" WHERE ("node_field_data"."nid" = "1") AND ("node_field_revision"."revision_translation_affected" = 1) GROUP BY "base_table"."vid", "base_table"."nid" ORDER BY "base_table"."vid" DESC LIMIT 1 OFFSET 0',
      'SELECT "revision"."vid" AS "vid", "revision"."langcode" AS "langcode", "revision"."revision_uid" AS "revision_uid", "revision"."revision_timestamp" AS "revision_timestamp", "revision"."revision_log" AS "revision_log", "revision"."revision_default" AS "revision_default", "base"."nid" AS "nid", "base"."type" AS "type", "base"."uuid" AS "uuid", CASE "base"."vid" WHEN "revision"."vid" THEN 1 ELSE 0 END AS "isDefaultRevision" FROM "node" "base" INNER JOIN "node_revision" "revision" ON "revision"."nid" = "base"."nid" AND "revision"."vid" IN (75)',
      'SELECT "revision".* FROM "node_field_revision" "revision" WHERE ("revision"."vid" IN (75)) AND ("revision"."vid" IN ("75")) ORDER BY "revision"."vid" ASC',
      'SELECT "t".* FROM "node_revision__field_cooking_time" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_difficulty" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_ingredients" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_media_image" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_number_of_servings" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_preparation_time" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_recipe_category" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_recipe_instruction" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_summary" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_tags" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__layout_builder__layout" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node_revision" "base_table" LEFT OUTER JOIN "node_revision" "base_table_2" ON "base_table"."nid" = "base_table_2"."nid" AND "base_table"."vid" < "base_table_2"."vid" INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE ("base_table_2"."nid" IS NULL) AND ("node_field_data"."nid" = "1")',
      'SELECT "revision"."vid" AS "vid", "revision"."langcode" AS "langcode", "revision"."revision_uid" AS "revision_uid", "revision"."revision_timestamp" AS "revision_timestamp", "revision"."revision_log" AS "revision_log", "revision"."revision_default" AS "revision_default", "base"."nid" AS "nid", "base"."type" AS "type", "base"."uuid" AS "uuid", CASE "base"."vid" WHEN "revision"."vid" THEN 1 ELSE 0 END AS "isDefaultRevision" FROM "node" "base" INNER JOIN "node_revision" "revision" ON "revision"."nid" = "base"."nid" AND "revision"."vid" IN (75)',
      'SELECT "revision".* FROM "node_field_revision" "revision" WHERE ("revision"."vid" IN (75)) AND ("revision"."vid" IN ("75")) ORDER BY "revision"."vid" ASC',
      'SELECT "t".* FROM "node_revision__field_cooking_time" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_difficulty" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_ingredients" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_media_image" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_number_of_servings" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_preparation_time" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_recipe_category" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_recipe_instruction" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_summary" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_tags" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__layout_builder__layout" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "revision"."vid" AS "vid", "revision"."langcode" AS "langcode", "revision"."revision_uid" AS "revision_uid", "revision"."revision_timestamp" AS "revision_timestamp", "revision"."revision_log" AS "revision_log", "revision"."revision_default" AS "revision_default", "base"."nid" AS "nid", "base"."type" AS "type", "base"."uuid" AS "uuid", CASE "base"."vid" WHEN "revision"."vid" THEN 1 ELSE 0 END AS "isDefaultRevision" FROM "node" "base" INNER JOIN "node_revision" "revision" ON "revision"."nid" = "base"."nid" AND "revision"."vid" IN (75)',
      'SELECT "revision".* FROM "node_field_revision" "revision" WHERE ("revision"."vid" IN (75)) AND ("revision"."vid" IN ("75")) ORDER BY "revision"."vid" ASC',
      'SELECT "t".* FROM "node_revision__field_cooking_time" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_difficulty" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_ingredients" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_media_image" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_number_of_servings" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_preparation_time" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_recipe_category" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_recipe_instruction" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_summary" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_tags" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__layout_builder__layout" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "revision"."vid" AS "vid", "revision"."langcode" AS "langcode", "revision"."revision_uid" AS "revision_uid", "revision"."revision_timestamp" AS "revision_timestamp", "revision"."revision_log" AS "revision_log", "revision"."revision_default" AS "revision_default", "base"."nid" AS "nid", "base"."type" AS "type", "base"."uuid" AS "uuid", CASE "base"."vid" WHEN "revision"."vid" THEN 1 ELSE 0 END AS "isDefaultRevision" FROM "node" "base" INNER JOIN "node_revision" "revision" ON "revision"."nid" = "base"."nid" AND "revision"."vid" IN (75)',
      'SELECT "revision".* FROM "node_field_revision" "revision" WHERE ("revision"."vid" IN (75)) AND ("revision"."vid" IN ("75")) ORDER BY "revision"."vid" ASC',
      'SELECT "t".* FROM "node_revision__field_cooking_time" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_difficulty" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_ingredients" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_media_image" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_number_of_servings" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_preparation_time" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_recipe_category" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_recipe_instruction" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_summary" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_tags" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__layout_builder__layout" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "base_table"."revision_id" AS "revision_id", "base_table"."id" AS "id" FROM "content_moderation_state_revision" "base_table" INNER JOIN "content_moderation_state_field_revision" "content_moderation_state_field_revision" ON "content_moderation_state_field_revision"."revision_id" = "base_table"."revision_id" WHERE ("content_moderation_state_field_revision"."content_entity_type_id" LIKE "node" ESCAPE \'\\\\\') AND ("content_moderation_state_field_revision"."content_entity_id" = "1") AND ("content_moderation_state_field_revision"."content_entity_revision_id" = "76") AND ("content_moderation_state_field_revision"."workflow" = "editorial") AND ("content_moderation_state_field_revision"."langcode" = "en") ORDER BY "base_table"."revision_id" DESC',
      'SELECT "revision"."revision_id" AS "revision_id", "revision"."langcode" AS "langcode", "revision"."revision_default" AS "revision_default", "base"."id" AS "id", "base"."uuid" AS "uuid", CASE "base"."revision_id" WHEN "revision"."revision_id" THEN 1 ELSE 0 END AS "isDefaultRevision" FROM "content_moderation_state" "base" INNER JOIN "content_moderation_state_revision" "revision" ON "revision"."id" = "base"."id" AND "revision"."revision_id" IN (76)',
      'SELECT "revision".* FROM "content_moderation_state_field_revision" "revision" WHERE ("revision"."revision_id" IN (76)) AND ("revision"."revision_id" IN ("76")) ORDER BY "revision"."revision_id" ASC',
      'SELECT "revision"."vid" AS "vid", "revision"."langcode" AS "langcode", "revision"."revision_uid" AS "revision_uid", "revision"."revision_timestamp" AS "revision_timestamp", "revision"."revision_log" AS "revision_log", "revision"."revision_default" AS "revision_default", "base"."nid" AS "nid", "base"."type" AS "type", "base"."uuid" AS "uuid", CASE "base"."vid" WHEN "revision"."vid" THEN 1 ELSE 0 END AS "isDefaultRevision" FROM "node" "base" INNER JOIN "node_revision" "revision" ON "revision"."nid" = "base"."nid" AND "revision"."vid" IN (75)',
      'SELECT "revision".* FROM "node_field_revision" "revision" WHERE ("revision"."vid" IN (75)) AND ("revision"."vid" IN ("75")) ORDER BY "revision"."vid" ASC',
      'SELECT "t".* FROM "node_revision__field_cooking_time" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_difficulty" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_ingredients" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_media_image" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_number_of_servings" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_preparation_time" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_recipe_category" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_recipe_instruction" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_summary" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_tags" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__layout_builder__layout" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "revision"."vid" AS "vid", "revision"."langcode" AS "langcode", "revision"."revision_uid" AS "revision_uid", "revision"."revision_timestamp" AS "revision_timestamp", "revision"."revision_log" AS "revision_log", "revision"."revision_default" AS "revision_default", "base"."nid" AS "nid", "base"."type" AS "type", "base"."uuid" AS "uuid", CASE "base"."vid" WHEN "revision"."vid" THEN 1 ELSE 0 END AS "isDefaultRevision" FROM "node" "base" INNER JOIN "node_revision" "revision" ON "revision"."nid" = "base"."nid" AND "revision"."vid" IN (75)',
      'SELECT "revision".* FROM "node_field_revision" "revision" WHERE ("revision"."vid" IN (75)) AND ("revision"."vid" IN ("75")) ORDER BY "revision"."vid" ASC',
      'SELECT "t".* FROM "node_revision__field_cooking_time" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_difficulty" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_ingredients" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_media_image" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_number_of_servings" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_preparation_time" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_recipe_category" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_recipe_instruction" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_summary" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_tags" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__layout_builder__layout" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "config"."name" AS "name" FROM "config" "config" WHERE ("collection" = "") AND ("name" LIKE "workflows.workflow.%" ESCAPE \'\\\\\') ORDER BY "collection" ASC, "name" ASC',
      'SELECT "revision"."vid" AS "vid", "revision"."langcode" AS "langcode", "revision"."revision_uid" AS "revision_uid", "revision"."revision_timestamp" AS "revision_timestamp", "revision"."revision_log" AS "revision_log", "revision"."revision_default" AS "revision_default", "base"."nid" AS "nid", "base"."type" AS "type", "base"."uuid" AS "uuid", CASE "base"."vid" WHEN "revision"."vid" THEN 1 ELSE 0 END AS "isDefaultRevision" FROM "node" "base" INNER JOIN "node_revision" "revision" ON "revision"."nid" = "base"."nid" AND "revision"."vid" IN (75)',
      'SELECT "revision".* FROM "node_field_revision" "revision" WHERE ("revision"."vid" IN (75)) AND ("revision"."vid" IN ("75")) ORDER BY "revision"."vid" ASC',
      'SELECT "t".* FROM "node_revision__field_cooking_time" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_difficulty" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_ingredients" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_media_image" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_number_of_servings" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_preparation_time" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_recipe_category" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_recipe_instruction" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_summary" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__field_tags" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "t".* FROM "node_revision__layout_builder__layout" "t" WHERE ("revision_id" IN ("75")) AND ("deleted" = 0) AND ("langcode" IN ("en", "es", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."alias" LIKE "/recipes" ESCAPE \'\\\\\') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC',
      'SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."alias" LIKE "/node" ESCAPE \'\\\\\') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC',
      'SELECT "menu_tree"."menu_name" AS "menu_name", "menu_tree"."route_name" AS "route_name", "menu_tree"."route_parameters" AS "route_parameters", "menu_tree"."url" AS "url", "menu_tree"."title" AS "title", "menu_tree"."description" AS "description", "menu_tree"."parent" AS "parent", "menu_tree"."weight" AS "weight", "menu_tree"."options" AS "options", "menu_tree"."expanded" AS "expanded", "menu_tree"."enabled" AS "enabled", "menu_tree"."provider" AS "provider", "menu_tree"."metadata" AS "metadata", "menu_tree"."class" AS "class", "menu_tree"."form_class" AS "form_class", "menu_tree"."id" AS "id" FROM "menu_tree" "menu_tree" WHERE ("route_name" = "entity.node.canonical") AND ("route_param_key" = "node=1") AND ("menu_name" = "footer") ORDER BY "depth" ASC, "weight" ASC, "id" ASC',
      'INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("theme_registry:runtime:umami:Drupal\Core\Utility\ThemeRegistry", "LOCK_ID", "EXPIRE")',
      'DELETE FROM "semaphore"  WHERE ("name" = "theme_registry:runtime:umami:Drupal\Core\Utility\ThemeRegistry") AND ("value" = "LOCK_ID")',
    ];
    $recorded_queries = $performance_data->getQueries();
    $this->assertSame($expected_queries, $recorded_queries);
    $this->assertSame(170, $performance_data->getQueryCount());

    // Assert cache and cache tag values.
    $this->assertSame($performance_data->getCacheGetCount(), 252);
    $this->assertSame($performance_data->getCacheSetCount(), 40);
    $this->assertSame($performance_data->getCacheDeleteCount(), 0);
    $this->assertSame(62, $performance_data->getCacheTagChecksumCount());
    $this->assertSame(92, $performance_data->getCacheTagIsValidCount());
    $this->assertSame(0, $performance_data->getCacheTagInvalidationCount());

    // Assert script and stylesheet values.
    $this->assertSame(1, $performance_data->getScriptCount());
    $this->assertCountBetween(6500, 7500, $performance_data->getScriptBytes());
    $this->assertSame(2, $performance_data->getStylesheetCount());
    $this->assertCountBetween(41000, 42000, $performance_data->getStylesheetBytes());
  }

  /**
   * Clear caches.
   */
  protected function clearCaches(): void {
    foreach (Cache::getBins() as $bin) {
      $bin->deleteAll();
    }
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\demo_umami\FunctionalJavascript;

use Drupal\Core\Cache\Cache;
use Drupal\FunctionalJavascriptTests\PerformanceTestBase;

// cspell:ignore languageswitcher

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
    // Request the node page twice then clear caches, this allows asset
    // aggregate requests to complete so they are excluded from the performance
    // test itself. Including the asset aggregates would lead to
    // a non-deterministic test since they happen in parallel and therefore post
    // response tasks run in different orders each time.
    $this->drupalGet('node/1');
    $this->drupalGet('node/1');
    $this->clearCaches();
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'umamiNodePageColdCache');
    $this->assertSession()->pageTextContains('quiche');

    $expected = [
      'QueryCount' => 458,
      'CacheSetCount' => 441,
      'CacheDeleteCount' => 0,
      'CacheTagLookupQueryCount' => 43,
      'CacheTagInvalidationCount' => 0,
      'ScriptCount' => 1,
      'ScriptBytes' => 12000,
      'StylesheetCount' => 2,
      'StylesheetBytes' => 41350,
    ];
    $this->assertMetrics($expected, $performance_data);
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

    $expected = [
      'QueryCount' => 0,
      'CacheGetCount' => 1,
      'CacheSetCount' => 0,
      'CacheDeleteCount' => 0,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 1,
      'ScriptCount' => 1,
      'ScriptBytes' => 12000,
      'StylesheetCount' => 2,
      'StylesheetBytes' => 41350,
    ];
    $this->assertMetrics($expected, $performance_data);
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
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'umamiNodePageCoolCache');
    $this->assertSession()->pageTextContains('quiche');

    $expected = [
      'QueryCount' => 191,
      'CacheGetCount' => 210,
      'CacheSetCount' => 65,
      'CacheDeleteCount' => 0,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 22,
      'ScriptCount' => 1,
      'ScriptBytes' => 12000,
      'StylesheetCount' => 2,
      'StylesheetBytes' => 41350,
    ];
    $this->assertMetrics($expected, $performance_data);
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
      'SELECT "menu_tree"."menu_name" AS "menu_name", "menu_tree"."route_name" AS "route_name", "menu_tree"."route_parameters" AS "route_parameters", "menu_tree"."url" AS "url", "menu_tree"."title" AS "title", "menu_tree"."description" AS "description", "menu_tree"."parent" AS "parent", "menu_tree"."weight" AS "weight", "menu_tree"."options" AS "options", "menu_tree"."expanded" AS "expanded", "menu_tree"."enabled" AS "enabled", "menu_tree"."provider" AS "provider", "menu_tree"."metadata" AS "metadata", "menu_tree"."class" AS "class", "menu_tree"."form_class" AS "form_class", "menu_tree"."id" AS "id" FROM "menu_tree" "menu_tree" WHERE ("route_name" = "entity.node.canonical") AND ("route_param_key" = "node=1") AND ("menu_name" = "footer") ORDER BY "depth" ASC, "weight" ASC, "id" ASC',
      'SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."alias" LIKE "/recipes" ESCAPE \'\\\\\') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC',
      'SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."alias" LIKE "/node" ESCAPE \'\\\\\') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC',
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
      'INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("theme_registry:runtime:umami:Drupal\Core\Utility\ThemeRegistry", "LOCK_ID", "EXPIRE")',
      'DELETE FROM "semaphore"  WHERE ("name" = "theme_registry:runtime:umami:Drupal\Core\Utility\ThemeRegistry") AND ("value" = "LOCK_ID")',
      'INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("active-trail:route:entity.node.canonical:route_parameters:a:1:{s:4:"node";s:1:"1";}:Drupal\Core\Cache\CacheCollector", "LOCK_ID", "EXPIRE")',
      'DELETE FROM "semaphore"  WHERE ("name" = "active-trail:route:entity.node.canonical:route_parameters:a:1:{s:4:"node";s:1:"1";}:Drupal\Core\Cache\CacheCollector") AND ("value" = "LOCK_ID")',
    ];
    $recorded_queries = $performance_data->getQueries();
    $this->assertSame($expected_queries, $recorded_queries);

    $expected = [
      'QueryCount' => 171,
      'CacheGetCount' => 202,
      'CacheGetCountByBin' => [
        'page' => 1,
        'config' => 66,
        'bootstrap' => 10,
        'discovery' => 67,
        'data' => 13,
        'entity' => 18,
        'dynamic_page_cache' => 1,
        'render' => 21,
        'default' => 3,
        'menu' => 2,
      ],
      'CacheSetCount' => 41,
      'CacheDeleteCount' => 0,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 22,
      'CacheTagGroupedLookups' => [
        [
          'entity_types',
          'route_match',
          'access_policies',
          'routes',
          'router',
          'entity_field_info',
          'entity_bundles',
          'local_task',
          'library_info',
        ],
        ['config:views.view.related_recipes'],
        ['config:core.extension', 'views_data'],
        ['node:10', 'node:3', 'node:6', 'node:7', 'node_list'],
        ['breakpoints'],
        [
          'config:core.entity_view_display.media.image.responsive_3x2',
          'config:image.style.large_3_2_2x',
          'config:image.style.large_3_2_768x512',
          'config:image.style.medium_3_2_2x',
          'config:image.style.medium_3_2_600x400',
          'config:responsive_image.styles.3_2_image',
          'media:1',
          'media_view',
          'rendered',
        ],
        ['media:21'],
        [
          'config:core.entity_view_display.node.recipe.card',
          'node_view',
          'user:6',
        ],
        ['media:7'],
        ['media:6'],
        ['media:3'],
        [
          'config:core.entity_view_display.node.recipe.full',
          'config:filter.format.basic_html',
          'node:1',
          'taxonomy_term:13',
          'taxonomy_term:22',
          'taxonomy_term:31',
        ],
        ['config:views.view.recipe_collections'],
        [
          'CACHE_MISS_IF_UNCACHEABLE_HTTP_METHOD:form',
          'block_view',
          'config:block.block.umami_search',
        ],
        [
          'config:block.block.umami_branding',
          'config:system.site',
        ],
        ['config:block.block.umami_messages'],
        ['config:block.block.umami_help'],
        [
          'block_content:1',
          'block_content:2',
          'config:block.block.umami_account_menu',
          'config:block.block.umami_banner_home',
          'config:block.block.umami_banner_recipes',
          'config:block.block.umami_breadcrumbs',
          'config:block.block.umami_content',
          'config:block.block.umami_disclaimer',
          'config:block.block.umami_footer',
          'config:block.block.umami_footer_promo',
          'config:block.block.umami_languageswitcher',
          'config:block.block.umami_local_tasks',
          'config:block.block.umami_main_menu',
          'config:block.block.umami_page_title',
          'config:block.block.umami_views_block__articles_aside_block_1',
          'config:block.block.umami_views_block__promoted_items_block_1',
          'config:block.block.umami_views_block__recipe_collections_block',
          'config:block_list',
          'config:configurable_language_list',
          'http_response',
        ],
        [
          'config:system.menu.account',
          'config:system.menu.footer',
          'block_content_view',
          'config:core.entity_view_display.block_content.footer_promo_block.default',
          'config:core.entity_view_display.media.image.medium_8_7',
          'config:image.style.medium_8_7',
          'file:37',
          'media:19',
          'config:system.menu.main',
          'taxonomy_term:1',
          'taxonomy_term:10',
          'taxonomy_term:11',
          'taxonomy_term:12',
          'taxonomy_term:14',
          'taxonomy_term:15',
          'taxonomy_term:16',
          'taxonomy_term:2',
          'taxonomy_term:3',
          'taxonomy_term:4',
          'taxonomy_term:5',
          'taxonomy_term:6',
          'taxonomy_term:7',
          'taxonomy_term:8',
          'taxonomy_term:9',
          'taxonomy_term_list',
        ],
        ['config:views.view.recipes'],
        ['config:workflows.workflow.editorial'],
        ['config:user.role.anonymous'],
      ],
      'ScriptCount' => 1,
      'ScriptBytes' => 12000,
      'StylesheetCount' => 2,
      'StylesheetBytes' => 41200,
    ];
    $this->assertMetrics($expected, $performance_data);
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

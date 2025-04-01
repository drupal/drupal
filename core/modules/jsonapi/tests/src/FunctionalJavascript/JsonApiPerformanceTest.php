<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\PerformanceTestBase;

/**
 * Tests performance for JSON:API routes.
 *
 * @group Common
 * @group #slow
 * @requires extension apcu
 */
class JsonApiPerformanceTest extends PerformanceTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['jsonapi', 'node'];

  /**
   * Tests performance of the navigation toolbar.
   */
  public function testGetIndividual(): void {

    $this->drupalCreateContentType(['type' => 'article']);
    \Drupal::service('router.builder')->rebuildIfNeeded();
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Example article',
      'uuid' => '677f9911-f002-4639-9891-5c39e8b00d9d',
    ]);

    $user = $this->drupalCreateUser();
    $user->addRole('administrator');
    $user->save();
    $this->drupalLogin($user);

    // Request the front page to ensure all cache collectors are fully
    // warmed, wait one second to ensure that the request finished processing.
    $this->drupalGet('');
    sleep(1);

    $url = Url::fromRoute('jsonapi.node--article.individual', ['entity' => $node->uuid()])->toString();
    $performance_data = $this->collectPerformanceData(function () use ($url) {
      $this->drupalGet($url);
    }, 'jsonapi_individual_cool_cache');

    $expected_queries = [
      'SELECT "session" FROM "sessions" WHERE "sid" = "SESSION_ID" LIMIT 0, 1',
      'SELECT * FROM "users_field_data" "u" WHERE "u"."uid" = "2" AND "u"."default_langcode" = 1',
      'SELECT "roles_target_id" FROM "user__roles" WHERE "entity_id" = "2"',
      'SELECT "base_table"."id" AS "id", "base_table"."path" AS "path", "base_table"."alias" AS "alias", "base_table"."langcode" AS "langcode" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."alias" LIKE "/jsonapi/node/article/677f9911-f002-4639-9891-5c39e8b00d9d" ESCAPE ' . "'\\\\'" . ') AND ("base_table"."langcode" IN ("en", "und")) ORDER BY "base_table"."langcode" ASC, "base_table"."id" DESC',
      'SELECT "name", "route", "fit" FROM "router" WHERE "pattern_outline" IN ( "/jsonapi/node/article/677f9911-f002-4639-9891-5c39e8b00d9d", "/jsonapi/node/article/%", "/jsonapi/node/%/%", "/jsonapi/%/article/677f9911-f002-4639-9891-5c39e8b00d9d", "/jsonapi/%/%/%", "/jsonapi/node/article", "/jsonapi/node/%", "/jsonapi/%/article", "/jsonapi/node", "/jsonapi/%", "/jsonapi/node/article/%"0 ) AND "number_parts" >= 4',
      'SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node" "base_table" INNER JOIN "node" "node" ON "node"."nid" = "base_table"."nid" INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE ("node"."uuid" IN ("677f9911-f002-4639-9891-5c39e8b00d9d")) AND ("node_field_data"."default_langcode" IN (1))',
      'SELECT "revision"."vid" AS "vid", "revision"."langcode" AS "langcode", "revision"."revision_uid" AS "revision_uid", "revision"."revision_timestamp" AS "revision_timestamp", "revision"."revision_log" AS "revision_log", "revision"."revision_default" AS "revision_default", "base"."nid" AS "nid", "base"."type" AS "type", "base"."uuid" AS "uuid", CASE "base"."vid" WHEN "revision"."vid" THEN 1 ELSE 0 END AS "isDefaultRevision" FROM "node" "base" INNER JOIN "node_revision" "revision" ON "revision"."vid" = "base"."vid" WHERE "base"."nid" IN (1)',
      'SELECT "revision".* FROM "node_field_revision" "revision" WHERE ("revision"."nid" IN (1)) AND ("revision"."vid" IN ("1")) ORDER BY "revision"."nid" ASC',
      'SELECT "t".* FROM "node__body" "t" WHERE ("entity_id" IN (1)) AND ("deleted" = 0) AND ("langcode" IN ("en", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT 1 AS "expression" FROM "path_alias" "base_table" WHERE ("base_table"."status" = 1) AND ("base_table"."path" LIKE "/jsonapi%" ESCAPE ' . "'\\\\'" . ') LIMIT 1 OFFSET 0',
      'SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node_revision" "base_table" LEFT OUTER JOIN "node_revision" "base_table_2" ON "base_table"."nid" = "base_table_2"."nid" AND "base_table"."vid" < "base_table_2"."vid" INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE ("base_table_2"."nid" IS NULL) AND ("node_field_data"."nid" = "1")',
      'SELECT "name", "route" FROM "router" WHERE "name" IN ( "jsonapi.node--article.node_type.relationship.get" )',
      'SELECT "name", "route" FROM "router" WHERE "name" IN ( "jsonapi.node--article.node_type.related" )',
      'SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node" "base_table" INNER JOIN "node" "node" ON "node"."nid" = "base_table"."nid" INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE ("node"."uuid" IN ("677f9911-f002-4639-9891-5c39e8b00d9d")) AND ("node_field_data"."default_langcode" IN (1))',
      'SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node" "base_table" INNER JOIN "node" "node" ON "node"."nid" = "base_table"."nid" INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE ("node"."uuid" IN ("677f9911-f002-4639-9891-5c39e8b00d9d")) AND ("node_field_data"."default_langcode" IN (1))',
      'SELECT "name", "route" FROM "router" WHERE "name" IN ( "jsonapi.node--article.revision_uid.relationship.get" )',
      'SELECT "name", "route" FROM "router" WHERE "name" IN ( "jsonapi.node--article.revision_uid.related" )',
      'SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node" "base_table" INNER JOIN "node" "node" ON "node"."nid" = "base_table"."nid" INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE ("node"."uuid" IN ("677f9911-f002-4639-9891-5c39e8b00d9d")) AND ("node_field_data"."default_langcode" IN (1))',
      'SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node" "base_table" INNER JOIN "node" "node" ON "node"."nid" = "base_table"."nid" INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE ("node"."uuid" IN ("677f9911-f002-4639-9891-5c39e8b00d9d")) AND ("node_field_data"."default_langcode" IN (1))',
      'SELECT "name", "route" FROM "router" WHERE "name" IN ( "jsonapi.node--article.uid.relationship.get" )',
      'SELECT "name", "route" FROM "router" WHERE "name" IN ( "jsonapi.node--article.uid.related" )',
      'SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node" "base_table" INNER JOIN "node" "node" ON "node"."nid" = "base_table"."nid" INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE ("node"."uuid" IN ("677f9911-f002-4639-9891-5c39e8b00d9d")) AND ("node_field_data"."default_langcode" IN (1))',
      'SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node" "base_table" INNER JOIN "node" "node" ON "node"."nid" = "base_table"."nid" INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE ("node"."uuid" IN ("677f9911-f002-4639-9891-5c39e8b00d9d")) AND ("node_field_data"."default_langcode" IN (1))',
      'SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node" "base_table" INNER JOIN "node" "node" ON "node"."nid" = "base_table"."nid" INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE ("node"."uuid" IN ("677f9911-f002-4639-9891-5c39e8b00d9d")) AND ("node_field_data"."default_langcode" IN (1))',
      'INSERT INTO "semaphore" ("name", "value", "expire") VALUES ("path_alias_prefix_list:Drupal\Core\Cache\CacheCollector", "LOCK_ID", "EXPIRE")',
      'DELETE FROM "semaphore"  WHERE ("name" = "path_alias_prefix_list:Drupal\Core\Cache\CacheCollector") AND ("value" = "LOCK_ID")',
    ];
    $recorded_queries = $performance_data->getQueries();
    $this->assertSame($expected_queries, $recorded_queries);

    $expected = [
      'QueryCount' => 26,
      'CacheGetCount' => 42,
      'CacheGetCountByBin' => [
        'config' => 8,
        'data' => 8,
        'bootstrap' => 5,
        'discovery' => 13,
        'entity' => 2,
        'default' => 4,
        'dynamic_page_cache' => 1,
        'jsonapi_normalizations' => 1,
      ],
      'CacheSetCount' => 16,
      'CacheSetCountByBin' => [
        'data' => 7,
        'entity' => 1,
        'default' => 3,
        'dynamic_page_cache' => 2,
        'jsonapi_normalizations' => 2,
        'bootstrap' => 1,
      ],
      'CacheDeleteCount' => 0,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 3,
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
        ['jsonapi_resource_types'],
        ['config:filter.format.plain_text', 'http_response', 'node:1'],
      ],
    ];
    $this->assertMetrics($expected, $performance_data);

    $url = Url::fromRoute('jsonapi.node--article.individual', ['entity' => $node->uuid()])->toString();
    $performance_data = $this->collectPerformanceData(function () use ($url) {
      $this->drupalGet($url);
    }, 'jsonapi_individual_hot_cache');

    $expected_queries = [
      'SELECT "session" FROM "sessions" WHERE "sid" = "SESSION_ID" LIMIT 0, 1',
      'SELECT * FROM "users_field_data" "u" WHERE "u"."uid" = "2" AND "u"."default_langcode" = 1',
      'SELECT "roles_target_id" FROM "user__roles" WHERE "entity_id" = "2"',
      'SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node" "base_table" INNER JOIN "node" "node" ON "node"."nid" = "base_table"."nid" INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE ("node"."uuid" IN ("677f9911-f002-4639-9891-5c39e8b00d9d")) AND ("node_field_data"."default_langcode" IN (1))',
    ];
    $recorded_queries = $performance_data->getQueries();
    $this->assertSame($expected_queries, $recorded_queries);

    $expected = [
      'QueryCount' => 4,
      'CacheGetCount' => 19,
      'CacheGetCountByBin' => [
        'config' => 6,
        'data' => 1,
        'discovery' => 5,
        'entity' => 1,
        'default' => 1,
        'bootstrap' => 3,
        'dynamic_page_cache' => 2,
      ],
      'CacheSetCount' => 0,
      'CacheDeleteCount' => 0,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 3,
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
        ['jsonapi_resource_types'],
        ['config:filter.format.plain_text', 'http_response', 'node:1'],
      ],
    ];
    $this->assertMetrics($expected, $performance_data);
    $this->assertSame(['jsonapi.resource_types'], $performance_data->getCacheOperations()['get']['default']);

    $node->save();

    $url = Url::fromRoute('jsonapi.node--article.individual', ['entity' => $node->uuid()])->toString();
    $performance_data = $this->collectPerformanceData(function () use ($url) {
      $this->drupalGet($url);
    }, 'jsonapi_node_individual_invalidated');

    $expected_queries = [
      'SELECT "session" FROM "sessions" WHERE "sid" = "SESSION_ID" LIMIT 0, 1',
      'SELECT * FROM "users_field_data" "u" WHERE "u"."uid" = "2" AND "u"."default_langcode" = 1',
      'SELECT "roles_target_id" FROM "user__roles" WHERE "entity_id" = "2"',
      'SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node" "base_table" INNER JOIN "node" "node" ON "node"."nid" = "base_table"."nid" INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE ("node"."uuid" IN ("677f9911-f002-4639-9891-5c39e8b00d9d")) AND ("node_field_data"."default_langcode" IN (1))',
      'SELECT "revision"."vid" AS "vid", "revision"."langcode" AS "langcode", "revision"."revision_uid" AS "revision_uid", "revision"."revision_timestamp" AS "revision_timestamp", "revision"."revision_log" AS "revision_log", "revision"."revision_default" AS "revision_default", "base"."nid" AS "nid", "base"."type" AS "type", "base"."uuid" AS "uuid", CASE "base"."vid" WHEN "revision"."vid" THEN 1 ELSE 0 END AS "isDefaultRevision" FROM "node" "base" INNER JOIN "node_revision" "revision" ON "revision"."vid" = "base"."vid" WHERE "base"."nid" IN (1)',
      'SELECT "revision".* FROM "node_field_revision" "revision" WHERE ("revision"."nid" IN (1)) AND ("revision"."vid" IN ("1")) ORDER BY "revision"."nid" ASC',
      'SELECT "t".* FROM "node__body" "t" WHERE ("entity_id" IN (1)) AND ("deleted" = 0) AND ("langcode" IN ("en", "und", "zxx")) ORDER BY "delta" ASC',
      'SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node_revision" "base_table" LEFT OUTER JOIN "node_revision" "base_table_2" ON "base_table"."nid" = "base_table_2"."nid" AND "base_table"."vid" < "base_table_2"."vid" INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE ("base_table_2"."nid" IS NULL) AND ("node_field_data"."nid" = "1")',
      'SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node" "base_table" INNER JOIN "node" "node" ON "node"."nid" = "base_table"."nid" INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE ("node"."uuid" IN ("677f9911-f002-4639-9891-5c39e8b00d9d")) AND ("node_field_data"."default_langcode" IN (1))',
      'SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node" "base_table" INNER JOIN "node" "node" ON "node"."nid" = "base_table"."nid" INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE ("node"."uuid" IN ("677f9911-f002-4639-9891-5c39e8b00d9d")) AND ("node_field_data"."default_langcode" IN (1))',
      'SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node" "base_table" INNER JOIN "node" "node" ON "node"."nid" = "base_table"."nid" INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE ("node"."uuid" IN ("677f9911-f002-4639-9891-5c39e8b00d9d")) AND ("node_field_data"."default_langcode" IN (1))',
      'SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node" "base_table" INNER JOIN "node" "node" ON "node"."nid" = "base_table"."nid" INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE ("node"."uuid" IN ("677f9911-f002-4639-9891-5c39e8b00d9d")) AND ("node_field_data"."default_langcode" IN (1))',
      'SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node" "base_table" INNER JOIN "node" "node" ON "node"."nid" = "base_table"."nid" INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE ("node"."uuid" IN ("677f9911-f002-4639-9891-5c39e8b00d9d")) AND ("node_field_data"."default_langcode" IN (1))',
      'SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node" "base_table" INNER JOIN "node" "node" ON "node"."nid" = "base_table"."nid" INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE ("node"."uuid" IN ("677f9911-f002-4639-9891-5c39e8b00d9d")) AND ("node_field_data"."default_langcode" IN (1))',
      'SELECT "base_table"."vid" AS "vid", "base_table"."nid" AS "nid" FROM "node" "base_table" INNER JOIN "node" "node" ON "node"."nid" = "base_table"."nid" INNER JOIN "node_field_data" "node_field_data" ON "node_field_data"."nid" = "base_table"."nid" WHERE ("node"."uuid" IN ("677f9911-f002-4639-9891-5c39e8b00d9d")) AND ("node_field_data"."default_langcode" IN (1))',
    ];
    $recorded_queries = $performance_data->getQueries();
    $this->assertSame($expected_queries, $recorded_queries);

    $expected = [
      'QueryCount' => 15,
      'CacheGetCount' => 43,
      'CacheGetCountByBin' => [
        'config' => 8,
        'data' => 8,
        'discovery' => 13,
        'entity' => 2,
        'default' => 4,
        'bootstrap' => 4,
        'dynamic_page_cache' => 2,
        'jsonapi_normalizations' => 2,
      ],
      'CacheSetCount' => 3,
      'CacheDeleteCount' => 0,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 3,
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
        ['jsonapi_resource_types'],
        ['config:filter.format.plain_text', 'http_response', 'node:1'],
      ],
    ];
    $this->assertMetrics($expected, $performance_data);
    $this->assertSame([
      'jsonapi.resource_types',
      'jsonapi.resource_type.node.article',
      'jsonapi.resource_type.node_type.node_type',
      'jsonapi.resource_type.user.user',
    ], $performance_data->getCacheOperations()['get']['default']);
  }

}

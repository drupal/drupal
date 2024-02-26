<?php

declare(strict_types=1);

namespace Drupal\Tests\demo_umami\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\PerformanceTestBase;

/**
 * Tests demo_umami profile performance.
 *
 * @group OpenTelemetry
 * @group #slow
 */
class OpenTelemetryAuthenticatedPerformanceTest extends PerformanceTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'demo_umami';

  protected function setUp(): void {
    parent::setUp();
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);
  }

  /**
   * Logs front page tracing data with an authenticated user and warm cache.
   */
  public function testFrontPageAuthenticatedWarmCache(): void {
    $this->drupalGet('<front>');
    $this->drupalGet('<front>');

    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('<front>');
    }, 'authenticatedFrontPage');

    $expected_queries = [
      'SELECT "session" FROM "sessions" WHERE "sid" = "SESSION_ID" LIMIT 0, 1',
      'SELECT * FROM "users_field_data" "u" WHERE "u"."uid" = "8" AND "u"."default_langcode" = 1',
      'SELECT "roles_target_id" FROM "user__roles" WHERE "entity_id" = "8"',
      'SELECT "config"."name" AS "name" FROM "config" "config" WHERE ("collection" = "") AND ("name" LIKE "language.entity.%" ESCAPE ' . "'\\\\'" . ') ORDER BY "collection" ASC, "name" ASC',
      'SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "system.maintenance_mode" ) AND "collection" = "state"',
      'SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "twig_extension_hash_prefix" ) AND "collection" = "state"',
      'SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "asset.css_js_query_string" ) AND "collection" = "state"',
      'SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "drupal.test_wait_terminate" ) AND "collection" = "state"',
      'SELECT "name", "value" FROM "key_value" WHERE "name" IN ( "system.cron_last" ) AND "collection" = "state"',
    ];
    $recorded_queries = $performance_data->getQueries();
    $this->assertSame($expected_queries, $recorded_queries);
    $this->assertSame(9, $performance_data->getQueryCount());
    $this->assertSame(45, $performance_data->getCacheGetCount());
    $this->assertSame(0, $performance_data->getCacheSetCount());
    $this->assertSame(0, $performance_data->getCacheDeleteCount());
    $this->assertSame(0, $performance_data->getCacheTagChecksumCount());
    $this->assertSame(13, $performance_data->getCacheTagIsValidCount());
    $this->assertSame(0, $performance_data->getCacheTagInvalidationCount());
  }

}

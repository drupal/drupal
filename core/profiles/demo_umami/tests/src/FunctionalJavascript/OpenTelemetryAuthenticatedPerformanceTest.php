<?php

declare(strict_types=1);

namespace Drupal\Tests\demo_umami\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\PerformanceTestBase;

/**
 * Tests demo_umami profile performance.
 *
 * @group OpenTelemetry
 * @group #slow
 * @requires extension apcu
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
      'SELECT * FROM "users_field_data" "u" WHERE "u"."uid" = "10" AND "u"."default_langcode" = 1',
      'SELECT "roles_target_id" FROM "user__roles" WHERE "entity_id" = "10"',
      'SELECT "config"."name" AS "name" FROM "config" "config" WHERE ("collection" = "") AND ("name" LIKE "language.entity.%" ESCAPE ' . "'\\\\'" . ') ORDER BY "collection" ASC, "name" ASC',
    ];
    $recorded_queries = $performance_data->getQueries();
    $this->assertSame($expected_queries, $recorded_queries);

    $expected = [
      'QueryCount' => 4,
      'CacheGetCount' => 42,
      'CacheSetCount' => 0,
      'CacheDeleteCount' => 0,
      'CacheTagChecksumCount' => 0,
      'CacheTagIsValidCount' => 11,
      'CacheTagInvalidationCount' => 0,
      'ScriptCount' => 1,
      'ScriptBytes' => 123850,
      'StylesheetCount' => 2,
      'StylesheetBytes' => 43600,
    ];
    $this->assertMetrics($expected, $performance_data);
  }

}

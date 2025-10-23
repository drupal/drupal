<?php

declare(strict_types=1);

namespace Drupal\Tests\demo_umami\FunctionalJavascript;

use Drupal\Core\Cache\Cache;
use Drupal\FunctionalJavascriptTests\PerformanceTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests demo_umami profile performance.
 */
#[Group('OpenTelemetry')]
#[Group('#slow')]
#[RequiresPhpExtension('apcu')]
#[RunTestsInSeparateProcesses]
class OpenTelemetryAuthenticatedPerformanceTest extends PerformanceTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'demo_umami';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
  }

  /**
   * Logs front page tracing data with an authenticated user and warm cache.
   */
  public function testFrontPageAuthenticatedWarmCache(): void {
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);
    $this->drupalGet('<front>');
    sleep(2);
    $this->drupalGet('<front>');
    sleep(2);

    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('<front>');
    }, 'authenticatedFrontPage');

    $expected_queries = [
      'SELECT "session" FROM "sessions" WHERE "sid" = "SESSION_ID" LIMIT 0, 1',
      'SELECT * FROM "users_field_data" "u" WHERE "u"."uid" = "10" AND "u"."default_langcode" = 1',
      'SELECT "roles_target_id" FROM "user__roles" WHERE "entity_id" = "10"',
    ];
    $recorded_queries = $performance_data->getQueries();
    $this->assertSame($expected_queries, $recorded_queries);

    $expected = [
      'QueryCount' => 3,
      'CacheGetCount' => 43,
      'CacheGetCountByBin' => [
        'config' => 22,
        'bootstrap' => 6,
        'discovery' => 5,
        'data' => 5,
        'dynamic_page_cache' => 2,
        'menu' => 1,
        'render' => 2,
      ],
      'CacheSetCount' => 0,
      'CacheDeleteCount' => 0,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 5,
      'ScriptCount' => 1,
      'ScriptBytes' => 73031,
      'StylesheetCount' => 2,
      'StylesheetBytes' => 39163,
    ];
    $this->assertMetrics($expected, $performance_data);
  }

  /**
   * Logs node page performance with an administrator.
   */
  public function testNodePageAdministrator(): void {
    $user = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($user);
    sleep(2);

    $this->drupalGet('node/1');
    sleep(2);
    $this->drupalGet('node/1');
    sleep(2);

    $this->clearCaches();

    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'administratorNodePage');

    $expected = [
      'QueryCount' => 523,
      'CacheGetCount' => 548,
      'CacheGetCountByBin' => [
        'config' => 201,
        'bootstrap' => 26,
        'discovery' => 112,
        'data' => 72,
        'dynamic_page_cache' => 2,
        'default' => 45,
        'entity' => 23,
        'render' => 39,
        'menu' => 28,
      ],
      'CacheSetCount' => 454,
      'CacheDeleteCount' => 0,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 47,
      'ScriptCount' => 3,
      'ScriptBytes' => 263500,
      'StylesheetCount' => 6,
      'StylesheetBytes' => 106000,
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

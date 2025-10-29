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
      'CacheGetCount' => 44,
      'CacheGetCountByBin' => [
        'config' => 22,
        'bootstrap' => 7,
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

    // Create a user with most important admin permissions, but not access to
    // contextual links. This is because contextual module makes an AJAX request
    // dependent on the content of browser local storage, which can make
    // performance testing indeterminate.
    $user = $this->drupalCreateUser([
      'administer nodes',
      'bypass node access',
      'access administration pages',
      'administer site configuration',
      'administer modules',
      'administer themes',
      'administer users',
      'access toolbar',
      'administer shortcuts',
      'administer media',
      'access files overview',
      'administer blocks',
      'administer block content',
      'administer taxonomy',
      'access site reports',
      'administer menu',
      'access announcements',
    ]);
    $this->drupalLogin($user);
    // This is a very heavy request so allow extra time for asset, image
    // derivative requests and post response tasks to finish.
    sleep(5);

    $this->drupalGet('node/1');
    sleep(2);
    $this->drupalGet('node/1');
    sleep(1);

    $this->clearCaches();

    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'administratorNodePage');

    $expected = [
      'QueryCount' => 524,
      'CacheGetCount' => 546,
      'CacheGetCountByBin' => [
        'config' => 201,
        'bootstrap' => 28,
        'discovery' => 112,
        'data' => 70,
        'dynamic_page_cache' => 2,
        'default' => 43,
        'entity' => 23,
        'render' => 39,
        'menu' => 28,
      ],
      'CacheSetCount' => 454,
      'CacheDeleteCount' => 0,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 47,
      'ScriptCount' => 3,
      'ScriptBytes' => 249750,
      'StylesheetCount' => 6,
      'StylesheetBytes' => 101000,
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

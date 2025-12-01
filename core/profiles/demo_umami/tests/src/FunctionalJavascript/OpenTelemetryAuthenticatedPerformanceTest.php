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
   * Logs authenticated tracing data.
   */
  public function testAuthenticatedPerformance(): void {
    // Replace toolbar with navigation and uninstall history to avoid AJAX
    // requests while recording performance data.
    \Drupal::service('module_installer')->uninstall(['toolbar', 'history']);
    \Drupal::service('module_installer')->install(['navigation']);
    $this->doTestFrontPageAuthenticatedWarmCache();
    $this->doTestNodePageAdministrator();
  }

  /**
   * Logs front page tracing data with an authenticated user and warm cache.
   */
  protected function doTestFrontPageAuthenticatedWarmCache(): void {
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
      'CacheGetCount' => 34,
      'CacheGetCountByBin' => [
        'config' => 12,
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
      'ScriptBytes' => 13150,
      'StylesheetCount' => 2,
      'StylesheetBytes' => 39163,
    ];
    $this->assertMetrics($expected, $performance_data);
  }

  /**
   * Logs node page performance with an administrator.
   */
  protected function doTestNodePageAdministrator(): void {
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
      'access site reports',
      'administer users',
      'access navigation',
      'administer shortcuts',
      'administer media',
      'access files overview',
      'administer blocks',
      'administer block content',
      'administer taxonomy',
      'administer menu',
    ]);

    $this->drupalLogin($user);

    // Ensure the asset cache warming request happens with empty caches,
    // otherwise the unique combination of assets for the performance request
    // may not have been created yet.
    $this->clearCaches();

    $this->drupalGet('node/1');
    sleep(1);
    $this->drupalGet('node/1');
    sleep(1);

    $this->clearCaches();
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'administratorNodePage');

    $expected = [
      'QueryCount' => 354,
      'CacheGetCount' => 349,
      'CacheGetCountByBin' => [
        'config' => 91,
        'bootstrap' => 16,
        'discovery' => 108,
        'data' => 23,
        'entity' => 25,
        'dynamic_page_cache' => 1,
        'default' => 22,
        'render' => 39,
        'menu' => 24,
      ],
      'CacheSetCount' => 341,
      'CacheDeleteCount' => 0,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 32,
      'ScriptCount' => 5,
      'ScriptBytes' => 198900,
      'StylesheetCount' => 8,
      'StylesheetBytes' => 78297,
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

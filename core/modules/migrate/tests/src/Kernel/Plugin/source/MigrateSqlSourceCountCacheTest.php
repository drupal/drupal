<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Kernel\Plugin\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate_sql_count_cache_test\Plugin\migrate\source\SqlCountCache;
use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests SqlBase source count caching.
 */
#[Group('migrate')]
#[CoversClass(SqlCountCache::class)]
#[CoversMethod(SqlBase::class, 'doCount')]
#[CoversMethod(SourcePluginBase::class, 'count')]
#[RunTestsInSeparateProcesses]
class MigrateSqlSourceCountCacheTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate_sql_count_cache_test'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    // All tests use the same source_data, expected_data, expected_count, and
    // high_water. The high water is set later to maintain the order of the
    // parameters.
    $data = [
      'source_data' => [
        'source_table' => [
          ['id' => 1],
          ['id' => 2],
          ['id' => 3],
          ['id' => 4],
        ],
      ],
      'expected_data' => [
        ['id' => 1],
        ['id' => 2],
        ['id' => 3],
        ['id' => 4],
      ],
      'expected_count' => 4,
    ];

    return [
      'uncached source count' => $data,
      'cached source count, auto-generated cache key' => $data + [
        'configuration' => [
          'cache_counts' => TRUE,
        ],
        'high_water' => NULL,
        'expected_cache_key' => 'sql_count_cache-dbed2396c230e025663091479993a206441bf1f9ae4e60ebf3b504e4a76ad471',
      ],
      'cached source count, auto-generated cache key for alternative source configuration' => $data + [
        'configuration' => [
          'cache_counts' => TRUE,
          'some_source_plugin_configuration_key' => 19920106,
        ],
        'high_water' => NULL,
        'expected_cache_key' => 'sql_count_cache-83c62856dd5afc011f32574bcdc11c595557d629e1d73045e9353df2441ec269',
      ],
      'cached source count, provided cache key' => $data + [
        'configuration' => [
          'cache_counts' => TRUE,
          'cache_key' => 'custom_cache_key_here',
        ],
        'high_water' => NULL,
        'expected_cache_key' => 'custom_cache_key_here',
      ],
    ];
  }

}

<?php

namespace Drupal\Tests\migrate\Kernel\Plugin\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;

/**
 * Test source counts are correctly cached.
 *
 * @group migrate
 */
class MigrationSourceCacheTest extends MigrateTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate_cache_counts_test'];

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->migrationPluginManager = $this->container->get('plugin.manager.migration');
  }

  /**
   * Tests that counts for the same plugin_id are not crossed.
   */
  public function testCacheCountsNotContaminated() {
    $migration_1_definition = [
      'source' => [
        'plugin' => 'cacheable_embedded_data',
        'cache_counts' => TRUE,
        'ids' => [
          'id' => [
            'type' => 'integer',
          ],
        ],
        'data_rows' => [
          [
            ['id' => 1],
          ],
        ],
      ],
    ];
    $migration_2_definition = [
      'source' => [
        'plugin' => 'cacheable_embedded_data',
        'cache_counts' => TRUE,
        'ids' => [
          'id' => [
            'type' => 'integer',
          ],
        ],
        'data_rows' => [
          ['id' => 1],
          ['id' => 2],
        ],
      ],
    ];

    $migration_1 = $this->migrationPluginManager->createStubMigration($migration_1_definition);
    $migration_2 = $this->migrationPluginManager->createStubMigration($migration_2_definition);
    $migration_1_source = $migration_1->getSourcePlugin();
    $migration_2_source = $migration_2->getSourcePlugin();

    // Verify correct counts when count is refreshed.
    $this->assertSame(1, $migration_1_source->count(TRUE));
    $this->assertSame(2, $migration_2_source->count(TRUE));

    // Verify correct counts are cached.
    $this->assertSame(1, $migration_1_source->count());
    $this->assertSame(2, $migration_2_source->count());

    // Verify the cache keys are different.
    $cache_key_property = new \ReflectionProperty(SourcePluginBase::class, 'cacheKey');
    $cache_key_property->setAccessible(TRUE);
    $this->assertNotEquals($cache_key_property->getValue($migration_1_source), $cache_key_property->getValue($migration_2_source));
  }

  /**
   * Test that values are pulled from the cache when appropriate.
   */
  public function testCacheCountsUsed() {
    $migration_definition = [
      'source' => [
        'plugin' => 'cacheable_embedded_data',
        'cache_counts' => TRUE,
        'ids' => [
          'id' => [
            'type' => 'integer',
          ],
        ],
        'data_rows' => [
          ['id' => 1],
          ['id' => 2],
        ],
      ],
    ];
    $migration = $this->migrationPluginManager->createStubMigration($migration_definition);
    $migration_source = $migration->getSourcePlugin();
    $this->assertSame(2, $migration_source->count());

    // Pollute the cache.
    $cache_key_property = new \ReflectionProperty($migration_source, 'cacheKey');
    $cache_key_property->setAccessible(TRUE);
    $cache_key = $cache_key_property->getValue($migration_source);
    \Drupal::cache('migrate')->set($cache_key, 7);
    $this->assertSame(7, $migration_source->count());
    $this->assertSame(2, $migration_source->count(TRUE));
  }

}

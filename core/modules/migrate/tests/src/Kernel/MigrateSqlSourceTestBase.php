<?php

namespace Drupal\Tests\migrate\Kernel;

use Drupal\Core\Cache\MemoryCounterBackendFactory;
use Drupal\sqlite\Driver\Database\sqlite\Connection;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Base class for tests of Migrate source plugins that use a database.
 */
abstract class MigrateSqlSourceTestBase extends MigrateSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    $container->register('cache_factory', MemoryCounterBackendFactory::class);
  }

  /**
   * Builds an in-memory SQLite database from a set of source data.
   *
   * @param array $source_data
   *   The source data, keyed by table name. Each table is an array containing
   *   the rows in that table.
   *
   * @return \Drupal\sqlite\Driver\Database\sqlite\Connection
   *   The SQLite database connection.
   */
  protected function getDatabase(array $source_data) {
    // Create an in-memory SQLite database. Plugins can interact with it like
    // any other database, and it will cease to exist when the connection is
    // closed.
    $connection_options = ['database' => ':memory:'];
    $pdo = Connection::open($connection_options);
    $connection = new Connection($pdo, $connection_options);

    // Create the tables and fill them with data.
    foreach ($source_data as $table => $rows) {
      // Use the biggest row to build the table schema.
      $counts = array_map('count', $rows);
      asort($counts);
      end($counts);
      $pilot = $rows[key($counts)];

      $connection->schema()
        ->createTable($table, [
          // SQLite uses loose affinity typing, so it's OK for every field to
          // be a text field.
          'fields' => array_map(function () {
            return ['type' => 'text'];
          }, $pilot),
        ]);

      $fields = array_keys($pilot);
      $insert = $connection->insert($table)->fields($fields);
      array_walk($rows, [$insert, 'values']);
      $insert->execute();
    }

    return $connection;
  }

  /**
   * Tests the source plugin against a particular data set.
   *
   * @param array $source_data
   *   The source data that the plugin will read. See getDatabase() for the
   *   expected format.
   * @param array $expected_data
   *   The result rows the plugin is expected to return.
   * @param int $expected_count
   *   (optional) How many rows the source plugin is expected to return.
   * @param array $configuration
   *   (optional) Configuration for the source plugin.
   * @param mixed $high_water
   *   (optional) The value of the high water field.
   * @param string|null $expected_cache_key
   *   (optional) The expected cache key.
   *
   * @dataProvider providerSource
   *
   * @requires extension pdo_sqlite
   */
  public function testSource(array $source_data, array $expected_data, $expected_count = NULL, array $configuration = [], $high_water = NULL, $expected_cache_key = NULL) {
    $plugin = $this->getPlugin($configuration);

    // Since we don't yet inject the database connection, we need to use a
    // reflection hack to set it in the plugin instance.
    $reflector = new \ReflectionObject($plugin);
    $property = $reflector->getProperty('database');
    $property->setAccessible(TRUE);
    $property->setValue($plugin, $this->getDatabase($source_data));

    /** @var MemoryCounterBackend $cache **/
    $cache = \Drupal::cache('migrate');
    if ($expected_cache_key) {
      // Verify the the computed cache key.
      $property = $reflector->getProperty('cacheKey');
      $property->setAccessible(TRUE);
      $this->assertSame($expected_cache_key, $property->getValue($plugin));

      // Cache miss prior to calling ::count().
      $this->assertFalse($cache->get($expected_cache_key, 'cache'));

      $this->assertSame([], $cache->getCounter('set'));
      $count = $plugin->count();
      $this->assertSame($expected_count, $count);
      $this->assertSame([$expected_cache_key => 1], $cache->getCounter('set'));

      // Cache hit afterwards.
      $cache_item = $cache->get($expected_cache_key, 'cache');
      $this->assertNotSame(FALSE, $cache_item, 'This is not a cache hit.');
      $this->assertSame($expected_count, $cache_item->data);
    }
    else {
      $this->assertSame([], $cache->getCounter('set'));
      $plugin->count();
      $this->assertSame([], $cache->getCounter('set'));
    }

    parent::testSource($source_data, $expected_data, $expected_count, $configuration, $high_water);
  }

}

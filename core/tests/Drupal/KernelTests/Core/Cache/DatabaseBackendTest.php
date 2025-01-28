<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Cache;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\DatabaseBackend;

/**
 * Unit test of the database backend using the generic cache unit test base.
 *
 * @group Cache
 */
class DatabaseBackendTest extends GenericCacheBackendUnitTestBase {

  /**
   * The max rows to use for test bins.
   *
   * @var int
   */
  protected static $maxRows = 100;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Creates a new instance of DatabaseBackend.
   *
   * @return \Drupal\Core\Cache\DatabaseBackend
   *   A new DatabaseBackend object.
   */
  protected function createCacheBackend($bin) {
    return new DatabaseBackend(
      $this->container->get('database'),
      $this->container->get('cache_tags.invalidator.checksum'),
      $bin,
      $this->container->get('serialization.phpserialize'),
      \Drupal::service(TimeInterface::class),
      static::$maxRows,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function testSetGet(): void {
    parent::testSetGet();
    $backend = $this->getCacheBackend();

    // Set up a cache ID that is not ASCII and longer than 255 characters so we
    // can test cache ID normalization.
    $cid_long = str_repeat('愛€', 500);
    $cached_value_long = $this->randomMachineName();
    $backend->set($cid_long, $cached_value_long);
    $this->assertSame($cached_value_long, $backend->get($cid_long)->data, "Backend contains the correct value for long, non-ASCII cache id.");

    $cid_short = '愛1€';
    $cached_value_short = $this->randomMachineName();
    $backend->set($cid_short, $cached_value_short);
    $this->assertSame($cached_value_short, $backend->get($cid_short)->data, "Backend contains the correct value for short, non-ASCII cache id.");

    // Set multiple items to test exceeding the chunk size.
    $backend->deleteAll();
    $items = [];
    for ($i = 0; $i <= DatabaseBackend::MAX_ITEMS_PER_CACHE_SET; $i++) {
      $items["test$i"]['data'] = $i;
    }
    $backend->setMultiple($items);
    $this->assertSame(DatabaseBackend::MAX_ITEMS_PER_CACHE_SET + 1, $this->getNumRows());
  }

  /**
   * Tests the row count limiting of cache bin database tables.
   */
  public function testGarbageCollection(): void {
    $backend = $this->getCacheBackend();
    $max_rows = static::$maxRows;

    $this->assertSame(0, (int) $this->getNumRows());

    // Fill to just the limit.
    for ($i = 0; $i < $max_rows; $i++) {
      // Ensure that each cache item created happens in a different millisecond,
      // by waiting 1 ms (1000 microseconds). The garbage collection might
      // otherwise keep less than exactly 100 records (which is acceptable for
      // real-world cases, but not for this test).
      usleep(1000);
      $backend->set("test$i", $i);
    }
    $this->assertSame($max_rows, $this->getNumRows());

    // Garbage collection has no effect.
    $backend->garbageCollection();
    $this->assertSame($max_rows, $this->getNumRows());

    // Go one row beyond the limit.
    $backend->set('test' . ($max_rows + 1), $max_rows + 1);
    $this->assertSame($max_rows + 1, $this->getNumRows());

    // Garbage collection removes one row: the oldest.
    $backend->garbageCollection();
    $this->assertSame($max_rows, $this->getNumRows());
    $this->assertFalse($backend->get('test0'));
  }

  /**
   * Gets the number of rows in the test cache bin database table.
   *
   * @return int
   *   The number of rows in the test cache bin database table.
   */
  protected function getNumRows(): int {
    $table = 'cache_' . $this->testBin;
    $connection = $this->container->get('database');
    $query = $connection->select($table);
    $query->addExpression('COUNT([cid])', 'cid');
    return (int) $query->execute()->fetchField();
  }

  /**
   * Tests that "cache_tags.invalidator.checksum" is backend overridable.
   */
  public function testCacheTagsInvalidatorChecksumIsBackendOverridable(): void {
    $definition = $this->container->getDefinition('cache_tags.invalidator.checksum');
    $this->assertTrue($definition->hasTag('backend_overridable'));
  }

  /**
   * Test that the service "cache.backend.database" is backend overridable.
   */
  public function testCacheBackendDatabaseIsBackendOverridable(): void {
    $definition = $this->container->getDefinition('cache.backend.database');
    $this->assertTrue($definition->hasTag('backend_overridable'));
  }

}

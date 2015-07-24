<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\MigrateSourceTest.
 */

namespace Drupal\Tests\migrate\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrateIdMapInterface;

/**
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 * @group migrate
 */
class MigrateSourceTest extends MigrateTestCase {

  /**
   * Override the migration config.
   *
   * @var array
   */
  protected $defaultMigrationConfiguration = [
    'id' => 'test_migration',
    'source' => [],
  ];

  /**
   * Test row data.
   *
   * @var array
   */
  protected $row = ['test_sourceid1' => '1', 'timestamp' => 500];

  /**
   * Test source ids.
   *
   * @var array
   */
  protected $sourceIds = ['test_sourceid1' => 'test_sourceid1'];

  /**
   * The migration entity.
   *
   * @var \Drupal\migrate\Entity\Migration
   */
  protected $migration;

  /**
   * The migrate executable.
   *
   * @var \Drupal\migrate\MigrateExecutable
   */
  protected $executable;

  /**
   * Get the source plugin to test.
   *
   * @param array $configuration
   *   The source configuration.
   * @param array $migrate_config
   *   The migration configuration to be used in parent::getMigration().
   * @param int $status
   *   The default status for the new rows to be imported.
   *
   * @return \Drupal\migrate\Plugin\MigrateSourceInterface
   *   A mocked source plugin.
   */
  protected function getSource($configuration = [], $migrate_config = [], $status = MigrateIdMapInterface::STATUS_NEEDS_UPDATE) {
    $this->migrationConfiguration = $this->defaultMigrationConfiguration + $migrate_config;
    $this->migration = parent::getMigration();
    $this->executable = $this->getMigrateExecutable($this->migration);

    // Update the idMap for Source so the default is that the row has already
    // been imported. This allows us to use the highwater mark to decide on the
    // outcome of whether we choose to import the row.
    $id_map_array = ['original_hash' => '', 'hash' => '', 'source_row_status' => $status];
    $this->idMap
      ->expects($this->any())
      ->method('getRowBySource')
      ->willReturn($id_map_array);

    $constructor_args = [$configuration, 'd6_action', [], $this->migration];
    $methods = ['getModuleHandler', 'fields', 'getIds', '__toString', 'getIterator', 'prepareRow', 'initializeIterator', 'calculateDependencies'];
    $source_plugin = $this->getMock('\Drupal\migrate\Plugin\migrate\source\SourcePluginBase', $methods, $constructor_args);

    $source_plugin
      ->expects($this->any())
      ->method('fields')
      ->willReturn([]);
    $source_plugin
      ->expects($this->any())
      ->method('getIds')
      ->willReturn([]);
    $source_plugin
      ->expects($this->any())
      ->method('__toString')
      ->willReturn('');
    $source_plugin
      ->expects($this->any())
      ->method('prepareRow')
      ->willReturn(empty($migrate_config['prepare_row_false']));
    $source_plugin
      ->expects($this->any())
      ->method('initializeIterator')
      ->willReturn([]);
    $iterator = new \ArrayIterator([$this->row]);
    $source_plugin
      ->expects($this->any())
      ->method('getIterator')
      ->willReturn($iterator);

    $module_handler = $this->getMock('\Drupal\Core\Extension\ModuleHandlerInterface');
    $source_plugin
      ->expects($this->any())
      ->method('getModuleHandler')
      ->willReturn($module_handler);

    $this->migration
      ->expects($this->any())
      ->method('getSourcePlugin')
      ->willReturn($source_plugin);

    return $this->migration->getSourcePlugin();
  }

  /**
   * @expectedException \Drupal\migrate\MigrateException
   */
  public function testHighwaterTrackChangesIncompatible() {
    $source_config = ['track_changes' => TRUE];
    $migration_config = ['highWaterProperty' => ['name' => 'something']];
    $this->getSource($source_config, $migration_config);
  }

  /**
   * Test that the source count is correct.
   */
  public function testCount() {

    $container = new ContainerBuilder();
    $container->register('cache.migrate', 'Drupal\Core\Cache\NullBackend')
      ->setArguments(['migrate']);
    \Drupal::setContainer($container);

    // Test that the basic count works.
    $source = $this->getSource();
    $this->assertEquals(1, $source->count());

    // Test caching the count works.
    $source = $this->getSource(['cache_counts' => TRUE]);
    $this->assertEquals(1, $source->count());

    // Test the skip argument.
    $source = $this->getSource(['skip_count' => TRUE]);
    $this->assertEquals(-1, $source->count());
  }

  /**
   * Test that we don't get a row if prepareRow() is false.
   */
  public function testPrepareRowFalse() {
    $source = $this->getSource([], ['prepare_row_false' => TRUE]);

    $source->rewind();
    $this->assertNull($source->current(), 'No row is available when prepareRow() is false.');
  }

  /**
   * Test that the when a source id is in the idList, we don't get a row.
   */
  public function testIdInList() {
    $source = $this->getSource([], ['idlist' => ['test_sourceid1']]);
    $source->rewind();

    $this->assertNull($source->current(), 'No row is available because id was in idList.');
  }

  /**
   * Test that $row->needsUpdate() works as expected.
   */
  public function testNextNeedsUpdate() {
    $source = $this->getSource();

    // $row->needsUpdate() === TRUE so we get a row.
    $source->rewind();
    $this->assertTrue(is_a($source->current(), 'Drupal\migrate\Row'), '$row->needsUpdate() is TRUE so we got a row.');

    // Test that we don't get a row when the incoming row is marked as imported.
    $source = $this->getSource([], [], MigrateIdMapInterface::STATUS_IMPORTED);
    $source->rewind();
    $this->assertNull($source->current(), 'Row was already imported, should be NULL');
  }

  /**
   * Test that an outdated highwater mark does not cause a row to be imported.
   */
  public function testOutdatedHighwater() {

    $source = $this->getSource([], [], MigrateIdMapInterface::STATUS_IMPORTED);

    // Set the originalHighwater to something higher than our timestamp.
    $this->migration
      ->expects($this->any())
      ->method('getHighwater')
      ->willReturn($this->row['timestamp'] + 1);

    // The current highwater mark is now higher than the row timestamp so no row
    // is expected.
    $source->rewind();
    $this->assertNull($source->current(), 'Original highwater mark is higher than incoming row timestamp.');
  }

  /**
   * Test that a highwater mark newer than our saved one imports a row.
   *
   * @throws \Exception
   */
  public function testNewHighwater() {

    // Set a highwater property field for source. Now we should have a row
    // because the row timestamp is greater than the current highwater mark.
    $source = $this->getSource([], ['highWaterProperty' => ['name' => 'timestamp']], MigrateIdMapInterface::STATUS_IMPORTED);

    $source->rewind();
    $this->assertTrue(is_a($source->current(), 'Drupal\migrate\Row'), 'Incoming row timestamp is greater than current highwater mark so we have a row.');
  }

  /**
   * Get a mock executable for the test.
   *
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   The migration entity.
   *
   * @return \Drupal\migrate\MigrateExecutable
   *   The migrate executable.
   */
  protected function getMigrateExecutable($migration) {
    $message = $this->getMock('Drupal\migrate\MigrateMessageInterface');
    return new MigrateExecutable($migration, $message);
  }

}

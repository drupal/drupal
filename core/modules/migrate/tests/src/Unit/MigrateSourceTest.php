<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\MigrateSourceTest.
 */

namespace Drupal\Tests\migrate\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;

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
   * Gets the source plugin to test.
   *
   * @param array $configuration
   *   (optional) The source configuration. Defaults to an empty array.
   * @param array $migrate_config
   *   (optional) The migration configuration to be used in
   *   parent::getMigration(). Defaults to an empty array.
   * @param int $status
   *   (optional) The default status for the new rows to be imported. Defaults
   *   to MigrateIdMapInterface::STATUS_NEEDS_UPDATE.
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
   * @covers ::__construct
   * @expectedException \Drupal\migrate\MigrateException
   */
  public function testHighwaterTrackChangesIncompatible() {
    $source_config = ['track_changes' => TRUE];
    $migration_config = ['highWaterProperty' => ['name' => 'something']];
    $this->getSource($source_config, $migration_config);
  }

  /**
   * Test that the source count is correct.
   *
   * @covers ::count
   */
  public function testCount() {
    // Mock the cache to validate set() receives appropriate arguments.
    $container = new ContainerBuilder();
    $cache = $this->getMock(CacheBackendInterface::class);
    $cache->expects($this->any())->method('set')
        ->with($this->isType('string'), $this->isType('int'), $this->isType('int'));
    $container->set('cache.migrate', $cache);
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
   * Test basic row preparation.
   *
   * @covers ::prepareRow
   */
  public function testPrepareRow() {
    $this->migrationConfiguration['id'] = 'test_migration';

    // Get a new migration with an id.
    $migration = $this->getMigration();
    $source = new StubSourcePlugin([], '', [], $migration);
    $row = new Row([], []);

    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $module_handler->invokeAll('migrate_prepare_row', [$row, $source, $migration])
      ->willReturn([TRUE, TRUE])
      ->shouldBeCalled();
    $module_handler->invokeAll('migrate_' . $migration->id() . '_prepare_row', [$row, $source, $migration])
      ->willReturn([TRUE, TRUE])
      ->shouldBeCalled();
    $source->setModuleHandler($module_handler->reveal());

    // Ensure we don't log this to the mapping table.
    $this->idMap->expects($this->never())
      ->method('saveIdMapping');

    $this->assertTrue($source->prepareRow($row));

    // Track_changes...
    $source = new StubSourcePlugin(['track_changes' => TRUE], '', [], $migration);
    $row2 = $this->prophesize(Row::class);
    $row2->rehash()
      ->shouldBeCalled();
    $module_handler->invokeAll('migrate_prepare_row', [$row2, $source, $migration])
      ->willReturn([TRUE, TRUE])
      ->shouldBeCalled();
    $module_handler->invokeAll('migrate_' . $migration->id() . '_prepare_row', [$row2, $source, $migration])
      ->willReturn([TRUE, TRUE])
      ->shouldBeCalled();
    $source->setModuleHandler($module_handler->reveal());
    $this->assertTrue($source->prepareRow($row2->reveal()));
  }

  /**
   * Test that global prepare hooks can skip rows.
   *
   * @covers ::prepareRow
   */
  public function testPrepareRowGlobalPrepareSkip() {
    $this->migrationConfiguration['id'] = 'test_migration';

    $migration = $this->getMigration();
    $source = new StubSourcePlugin([], '', [], $migration);
    $row = new Row([], []);

    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    // Return a failure from a prepare row hook.
    $module_handler->invokeAll('migrate_prepare_row', [$row, $source, $migration])
      ->willReturn([TRUE, FALSE, TRUE])
      ->shouldBeCalled();
    $module_handler->invokeAll('migrate_' . $migration->id() . '_prepare_row', [$row, $source, $migration])
      ->willReturn([TRUE, TRUE])
      ->shouldBeCalled();
    $source->setModuleHandler($module_handler->reveal());

    $this->idMap->expects($this->once())
      ->method('saveIdMapping')
      ->with($row, [], MigrateIdMapInterface::STATUS_IGNORED);

    $this->assertFalse($source->prepareRow($row));
  }

  /**
   * Test that migrate specific prepare hooks can skip rows.
   *
   * @covers ::prepareRow
   */
  public function testPrepareRowMigratePrepareSkip() {
    $this->migrationConfiguration['id'] = 'test_migration';

    $migration = $this->getMigration();
    $source = new StubSourcePlugin([], '', [], $migration);
    $row = new Row([], []);

    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    // Return a failure from a prepare row hook.
    $module_handler->invokeAll('migrate_prepare_row', [$row, $source, $migration])
      ->willReturn([TRUE, TRUE])
      ->shouldBeCalled();
    $module_handler->invokeAll('migrate_' . $migration->id() . '_prepare_row', [$row, $source, $migration])
      ->willReturn([TRUE, FALSE, TRUE])
      ->shouldBeCalled();
    $source->setModuleHandler($module_handler->reveal());

    $this->idMap->expects($this->once())
      ->method('saveIdMapping')
      ->with($row, [], MigrateIdMapInterface::STATUS_IGNORED);

    $this->assertFalse($source->prepareRow($row));
  }

  /**
   * Test that a skip exception during prepare hooks correctly skips.
   *
   * @covers ::prepareRow
   */
  public function testPrepareRowPrepareException() {
    $this->migrationConfiguration['id'] = 'test_migration';

    $migration = $this->getMigration();
    $source = new StubSourcePlugin([], '', [], $migration);
    $row = new Row([], []);

    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    // Return a failure from a prepare row hook.
    $module_handler->invokeAll('migrate_prepare_row', [$row, $source, $migration])
      ->willReturn([TRUE, TRUE])
      ->shouldBeCalled();
    $module_handler->invokeAll('migrate_' . $migration->id() . '_prepare_row', [$row, $source, $migration])
      ->willThrow(new MigrateSkipRowException())
      ->shouldBeCalled();
    $source->setModuleHandler($module_handler->reveal());

    // This will only be called on the first prepare because the second
    // explicitly avoids it.
    $this->idMap->expects($this->once())
      ->method('saveIdMapping')
      ->with($row, [], MigrateIdMapInterface::STATUS_IGNORED);
    $this->assertFalse($source->prepareRow($row));

    // Throw an exception the second time that avoids mapping.
    $e = new MigrateSkipRowException('', FALSE);
    $module_handler->invokeAll('migrate_' . $migration->id() . '_prepare_row', [$row, $source, $migration])
      ->willThrow($e)
      ->shouldBeCalled();
    $this->assertFalse($source->prepareRow($row));
  }

  /**
   * Gets a mock executable for the test.
   *
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   The migration entity.
   *
   * @return \Drupal\migrate\MigrateExecutable
   *   The migrate executable.
   */
  protected function getMigrateExecutable($migration) {
    /** @var \Drupal\migrate\MigrateMessageInterface $message */
    $message = $this->getMock('Drupal\migrate\MigrateMessageInterface');
    /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher */
    $event_dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    return new MigrateExecutable($migration, $message, $event_dispatcher);
  }

}

/**
 * Stubbed source plugin for testing base class implementations.
 */
class StubSourcePlugin extends SourcePluginBase {

  /**
   * Helper for setting internal module handler implementation.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    return [];
  }

}

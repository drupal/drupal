<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Tests\UnitTestCase;
use Drupal\migrate\MigrateStub;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Row;
use Prophecy\Argument;

/**
 * Tests the migrate stub service.
 *
 * @group migrate
 *
 * @coversDefaultClass \Drupal\migrate\MigrateStub
 */
class MigrateStubTest extends UnitTestCase {

  /**
   * The plugin manager prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $migrationPluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->migrationPluginManager = $this->prophesize(MigrationPluginManagerInterface::class);
  }

  /**
   * Tests stubbing.
   *
   * @covers ::createStub
   */
  public function testCreateStub(): void {
    $destination_plugin = $this->prophesize(MigrateDestinationInterface::class);
    $destination_plugin->import(Argument::type(Row::class))->willReturn(['id' => 2]);

    $source_plugin = $this->prophesize(MigrateSourceInterface::class);
    $source_plugin->getIds()->willReturn(['id' => ['type' => 'integer']]);

    $id_map = $this->prophesize(MigrateIdMapInterface::class);

    $migration = $this->prophesize(MigrationInterface::class);
    $migration->getIdMap()->willReturn($id_map->reveal());
    $migration->getDestinationPlugin(TRUE)->willReturn($destination_plugin->reveal());
    $migration->getProcessPlugins([])->willReturn([]);
    $migration->getProcess()->willReturn([]);
    $migration->getSourceConfiguration()->willReturn([]);
    $migration->getSourcePlugin()->willReturn($source_plugin->reveal());

    $this->migrationPluginManager->createInstances(['test_migration'])->willReturn([$migration->reveal()]);

    $stub = new MigrateStub($this->migrationPluginManager->reveal());

    $this->assertSame(['id' => 2], $stub->createStub('test_migration', ['id' => 1], []));
  }

  /**
   * Tests that an error is logged if the plugin manager throws an exception.
   */
  public function testExceptionOnPluginNotFound(): void {
    $this->migrationPluginManager->createInstances(['test_migration'])->willReturn([]);
    $this->expectException(PluginNotFoundException::class);
    $this->expectExceptionMessage("Plugin ID 'test_migration' was not found.");
    $stub = new MigrateStub($this->migrationPluginManager->reveal());
    $stub->createStub('test_migration', [1]);
  }

  /**
   * Tests that an error is logged on derived migrations.
   */
  public function testExceptionOnDerivedMigration(): void {
    $this->migrationPluginManager->createInstances(['test_migration'])->willReturn([
      'test_migration:d1' => $this->prophesize(MigrationInterface::class)->reveal(),
      'test_migration:d2' => $this->prophesize(MigrationInterface::class)->reveal(),
    ]);
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('Cannot stub derivable migration "test_migration".  You must specify the id of a specific derivative to stub.');
    $stub = new MigrateStub($this->migrationPluginManager->reveal());
    $stub->createStub('test_migration', [1]);
  }

}

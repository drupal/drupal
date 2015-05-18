<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\process\MigrationTest.
 */

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\process\Migration;

/**
 * Tests the migration process plugin.
 *
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\process\Migration
 * @group migrate
 */
class MigrationTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {

    $this->migrationConfiguration = [
      'id' => 'test',
      'process' => [],
      'source' => [],
    ];

    parent::setUp();
  }

  /**
   * Assert that exceptions during import are logged.
   * @expectedException \Drupal\migrate\MigrateSkipRowException
   * @covers ::transform
   */
  public function testSaveOnException() {

    // A bunch of mock objects to get thing working
    $migration = $this->getMigration();
    $migration_source = $this->getMock('\Drupal\migrate\Plugin\MigrateSourceInterface');
    $migration_source->expects($this->once())
      ->method('getIds')
      ->willReturn([]);
    $migration->expects($this->once())
      ->method('getSourcePlugin')
      ->willReturn($migration_source);
    $migration_destination = $this->getMock('\Drupal\migrate\Plugin\MigrateDestinationInterface');
    $migration->expects($this->once())
      ->method('getDestinationPlugin')
      ->willReturn($migration_destination);
    $storage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn([
        'id' => $migration
      ]);
    $manager = $this->getMockBuilder('\Drupal\migrate\Plugin\MigratePluginManager')
      ->disableOriginalConstructor()
      ->getMock();

    // Throw an exception during import so we can log it.
    $migration_destination->expects($this->once())
      ->method('import')
      ->willThrowException(new MigrateException());

    // Build our migration plugin.
    $plugin = new Migration(['migration' => []],
      'migration', // ?
      [],
      $migration,
      $storage,
      $manager);

    // Assert that we log exceptions thrown during the import.
    $this->migrateExecutable->expects($this->once())
      ->method('saveMessage');

    $plugin->transform('value', $this->migrateExecutable, $this->row, 'prop');
  }

}

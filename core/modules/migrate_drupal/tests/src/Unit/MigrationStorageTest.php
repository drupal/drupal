<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate_drupal\Unit\MigrationStorageTest.
 */

namespace Drupal\Tests\migrate_drupal\Unit;

use Drupal\Tests\migrate\Unit\MigrateTestCase;

/**
 * Tests the MigrationStorage load plugin.
 *
 * @group migrate_drupal
 */
class MigrationStorageTest extends MigrateTestCase {

  protected $migrationConfiguration = array('id' => 'test_migration', 'migrationClass' => 'Drupal\migrate_drupal\Entity\Migration');

  /**
     * Test that the entity load hooks are called on dynamic migrations.
   *
   * @dataProvider entityIdsDataProvider
     */
  public function testMigrationStorage($entity_ids) {
    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type
      ->expects($this->exactly(2))
      ->method('isStaticallyCacheable')
      ->willReturn(FALSE);

    $load_plugin = $this->getMock('Drupal\migrate_drupal\Plugin\MigrateLoadInterface');
    $load_plugin
      ->expects($this->once())
      ->method('loadMultiple')
      ->willReturn([]);

    $migration = $this->getMigration();
    $migration
      ->expects($this->once())
      ->method('getLoadPlugin')
      ->willReturn($load_plugin);

    $storage = $this->getMock('Drupal\migrate_drupal\TestMigrationStorage', ['postLoad', 'doLoadMultiple'], [$entity_type]);
    $storage
      ->expects($this->exactly(2))
      ->method('postLoad');
    $storage
      ->expects($this->once())
      ->method('doLoadMultiple')
      ->willReturn(['test_migration' => $migration]);

    $storage->loadMultiple($entity_ids);
  }

  /**
   * The data provider for migration storage.
   *
   * @return array
   *   The entity ids.
   */
  public function entityIdsDataProvider() {
    return [
      [['test_migration:bundle']],
      [NULL],
    ];
  }

}

namespace Drupal\migrate_drupal;

class TestMigrationStorage extends MigrationStorage {
  public function __construct($entity_type) {
    $this->entityType = $entity_type;
  }
}

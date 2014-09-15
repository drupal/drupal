<?php
/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\process\MigrationTest.
 */

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\Plugin\migrate\process\Migration;

/**
 * Test the Migration process plugin.
 *
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\process\Migration
 *
 * @group migrate
 */
class MigrationTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->plugin = new TestMigrationTest();
    $this->migrationConfiguration = array('id' => 'test_migration');
    parent::setUp();
  }

  /**
   * Test the no_stub setting.
   *
   * @covers ::transform
   *
   * @expectedException \Drupal\migrate\MigrateSkipRowException
   */
  public function testNoStub() {

    $migration = $this->getMigration();
    $this->plugin->migration = $migration;

    $storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->any())
      ->method('loadMultiple')
      ->willReturn(array($migration, $migration));
    $this->plugin->setMigrationStorage($storage);

    $this->plugin->setConfiguration(array(
      'migration' => array('test_migration', 'test_migration2'),
      'no_stub' => TRUE,
    ));

    $this->plugin->transform('test', $this->migrateExecutable, $this->row, 'test');
  }


}

class TestMigrationTest extends Migration {

  public function __construct() {
  }

  public function setConfiguration($configuration) {
    $this->configuration = $configuration;
  }

  public function setMigrationStorage($storage) {
    $this->migrationStorage = $storage;
  }

}

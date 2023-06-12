<?php

namespace Drupal\Tests\migrate_drupal\Kernel;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Tests that a migration can be instantiated without a database connection.
 *
 * @group migrate_drupal
 */
class MigrateMissingDatabaseTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate', 'migrate_drupal', 'node'];

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrationPluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->migrationPluginManager = \Drupal::service('plugin.manager.migration');

    // Set the 'migrate' database connection to use a missing host.
    $info = Database::getConnectionInfo('default')['default'];
    $info['host'] = 'does_not_exist';
    Database::addConnectionInfo('migrate', 'default', $info);
  }

  /**
   * Tests that a migration can be instantiated with the node module enabled.
   *
   * When the migrate_drupal and node modules are enabled, the migration
   * derivers call checkRequirements() whenever createInstance() is used. If the
   * database connection is not available, then Migration::setUpDatabase()
   * throws an exception. Check that the exception is caught and the migration
   * can still be used to access its IdMap.
   */
  public function testMissingDatabase(): void {
    $migration = $this->migrationPluginManager->createInstance('d7_node_type');
    $this->assertInstanceOf(MigrationInterface::class, $migration);
    $this->assertInstanceOf(MigrateIdMapInterface::class, $migration->getIdMap());
  }

}

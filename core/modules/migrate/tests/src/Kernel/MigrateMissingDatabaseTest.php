<?php

namespace Drupal\Tests\migrate\Kernel;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Tests that a SQL migration can be instantiated without a database connection.
 *
 * @group migrate
 */
class MigrateMissingDatabaseTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate', 'migrate_missing_database_test'];

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

    // Set the 'migrate' database connection to use a missing database.
    $info = Database::getConnectionInfo('default')['default'];
    $info['database'] = 'godot';
    Database::addConnectionInfo('migrate', 'default', $info);
  }

  /**
   * Tests a SQL migration without the database connection.
   *
   * - The migration can be instantiated.
   * - The checkRequirements() method throws a RequirementsException.
   */
  public function testMissingDatabase(): void {
    $migration = $this->migrationPluginManager->createInstance('missing_database');
    $this->assertInstanceOf(MigrationInterface::class, $migration);
    $this->assertInstanceOf(MigrateIdMapInterface::class, $migration->getIdMap());
    $this->expectException(RequirementsException::class);
    $this->expectExceptionMessage('No database connection available for source plugin migrate_missing_database_test');
    $migration->checkRequirements();
  }

}

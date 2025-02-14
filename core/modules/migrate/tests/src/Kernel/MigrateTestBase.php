<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Kernel;

use Drupal\Core\Database\Database;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Creates abstract base class for migration tests.
 */
abstract class MigrateTestBase extends KernelTestBase implements MigrateMessageInterface {

  /**
   * TRUE to collect messages instead of displaying them.
   *
   * @var bool
   */
  protected $collectMessages = FALSE;

  /**
   * A two dimensional array of messages.
   *
   * The first key is the type of message, the second is just numeric. Values
   * are the messages.
   *
   * @var array
   */
  protected $migrateMessages;

  /**
   * The primary migration being tested.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The source database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $sourceDatabase;

  /**
   * A logger prophecy object.
   *
   * Using ::setTestLogger(), this prophecy will be configured and injected into
   * the container. Using $this->logger->function(args)->shouldHaveBeenCalled()
   * you can assert that the logger was called.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->createMigrationConnection();
    $this->sourceDatabase = Database::getConnection('default', 'migrate');
    // Attach the original test prefix as a database, for SQLite to attach its
    // database file.
    $this->sourceDatabase->attachDatabase(substr($this->sourceDatabase->getConnectionOptions()['prefix'], 0, -1));
  }

  /**
   * Changes the database connection to the prefixed one.
   *
   * @todo Remove when we don't use global. https://www.drupal.org/node/2552791
   */
  private function createMigrationConnection() {
    // If the backup already exists, something went terribly wrong.
    // This case is possible, because database connection info is a static
    // global state construct on the Database class, which at least persists
    // for all test methods executed in one PHP process.
    if (Database::getConnectionInfo('simpletest_original_migrate')) {
      throw new \RuntimeException("Bad Database connection state: 'simpletest_original_migrate' connection key already exists. Broken test?");
    }

    // Clone the current connection and replace the current prefix.
    $connection_info = Database::getConnectionInfo('migrate');
    if ($connection_info) {
      Database::renameConnection('migrate', 'simpletest_original_migrate');
    }
    $connection_info = Database::getConnectionInfo('default');
    foreach ($connection_info as $target => $value) {
      $prefix = $value['prefix'];
      // Tests use 7 character prefixes at most so this can't cause collisions.
      $connection_info[$target]['prefix'] = $prefix . '0';
    }
    Database::addConnectionInfo('migrate', 'default', $connection_info['default']);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $this->cleanupMigrateConnection();
    parent::tearDown();
    $this->collectMessages = FALSE;
    unset($this->migration, $this->migrateMessages);
  }

  /**
   * Cleans up the test migrate connection.
   *
   * @todo Remove when we don't use global. https://www.drupal.org/node/2552791
   */
  private function cleanupMigrateConnection() {
    Database::removeConnection('migrate');
    $original_connection_info = Database::getConnectionInfo('simpletest_original_migrate');
    if ($original_connection_info) {
      Database::renameConnection('simpletest_original_migrate', 'migrate');
    }
  }

  /**
   * Prepare any dependent migrations.
   *
   * @param array $id_mappings
   *   A list of ID mappings keyed by migration IDs. Each ID mapping is a list
   *   of two arrays, the first are source IDs and the second are destination
   *   IDs.
   */
  protected function prepareMigrations(array $id_mappings) {
    $manager = $this->container->get('plugin.manager.migration');
    foreach ($id_mappings as $migration_id => $data) {
      foreach ($manager->createInstances($migration_id) as $migration) {
        $id_map = $migration->getIdMap();
        $id_map->setMessage($this);
        $source_ids = $migration->getSourcePlugin()->getIds();
        foreach ($data as $id_mapping) {
          $row = new Row(array_combine(array_keys($source_ids), $id_mapping[0]), $source_ids);
          $id_map->saveIdMapping($row, $id_mapping[1]);
        }
      }
    }
  }

  /**
   * Modify a migration's configuration before executing it.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration to execute.
   */
  protected function prepareMigration(MigrationInterface $migration) {
    // Default implementation for test classes not requiring modification.
  }

  /**
   * Executes a single migration.
   *
   * @param string|\Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration to execute, or its ID.
   */
  protected function executeMigration($migration) {
    if (is_string($migration)) {
      $this->migration = $this->getMigration($migration);
    }
    else {
      $this->migration = $migration;
    }
    if ($this instanceof MigrateDumpAlterInterface) {
      $this->migrateDumpAlter($this);
    }

    $this->prepareMigration($this->migration);
    (new MigrateExecutable($this->migration, $this))->import();
  }

  /**
   * Executes a set of migrations in dependency order.
   *
   * @param string[] $ids
   *   Array of migration IDs, in any order. If any of these migrations use a
   *   deriver, the derivatives will be made before execution.
   */
  protected function executeMigrations(array $ids) {
    $manager = $this->container->get('plugin.manager.migration');
    $instances = $manager->createInstances($ids);
    array_walk($instances, [$this, 'executeMigration']);
  }

  /**
   * {@inheritdoc}
   */
  public function display($message, $type = 'status') {
    if ($this->collectMessages) {
      $this->migrateMessages[$type][] = $message;
    }
    else {
      $this->assertEquals('status', $type, $message);
    }
  }

  /**
   * Start collecting messages and erase previous messages.
   */
  public function startCollectingMessages() {
    $this->collectMessages = TRUE;
    $this->migrateMessages = [];
  }

  /**
   * Stop collecting messages.
   */
  public function stopCollectingMessages() {
    $this->collectMessages = FALSE;
  }

  /**
   * Records a failure in the map table of a specific migration.
   *
   * This is done in order to test scenarios which require a failed row.
   *
   * @param string|\Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration entity, or its ID.
   * @param array $row
   *   The raw source row which "failed".
   * @param int $status
   *   (optional) The failure status. Should be one of the
   *   MigrateIdMapInterface::STATUS_* constants. Defaults to
   *   MigrateIdMapInterface::STATUS_FAILED.
   */
  protected function mockFailure($migration, array $row, $status = MigrateIdMapInterface::STATUS_FAILED) {
    if (is_string($migration)) {
      $migration = $this->getMigration($migration);
    }
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $destination = array_map(function () {
      return NULL;
    }, $migration->getDestinationPlugin()->getIds());
    $row = new Row($row, $migration->getSourcePlugin()->getIds());
    $migration->getIdMap()->saveIdMapping($row, $destination, $status);
  }

  /**
   * Gets the migration plugin.
   *
   * @param string $plugin_id
   *   The plugin ID of the migration to get.
   *
   * @return \Drupal\migrate\Plugin\Migration
   *   The migration plugin.
   */
  protected function getMigration($plugin_id) {
    return $this->container->get('plugin.manager.migration')->createInstance($plugin_id);
  }

  /**
   * Injects the test logger into the container.
   */
  protected function setTestLogger() {
    $this->logger = $this->prophesize(LoggerChannelInterface::class);
    $this->container->set('logger.channel.migrate', $this->logger->reveal());
    \Drupal::setContainer($this->container);
  }

}

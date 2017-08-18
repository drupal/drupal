<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Kernel\SqlBaseTest.
 */

namespace Drupal\Tests\migrate\Kernel;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\migrate\source\TestSqlBase;
use Drupal\Core\Database\Database;

/**
 * Tests the functionality of SqlBase.
 *
 * @group migrate
 */
class SqlBaseTest extends MigrateTestBase {

  /**
   * Tests different connection types.
   */
  public function testConnectionTypes() {
    $sql_base = new TestSqlBase();

    // Verify that falling back to the default 'migrate' connection (defined in
    // the base class) works.
    $this->assertSame($sql_base->getDatabase()->getTarget(), 'default');
    $this->assertSame($sql_base->getDatabase()->getKey(), 'migrate');

    // Verify the fallback state key overrides the 'migrate' connection.
    $target = 'test_fallback_target';
    $key = 'test_fallback_key';
    $config = ['target' => $target, 'key' => $key];
    $database_state_key = 'test_fallback_state';
    \Drupal::state()->set($database_state_key, $config);
    \Drupal::state()->set('migrate.fallback_state_key', $database_state_key);
    // Create a test connection using the default database configuration.
    Database::addConnectionInfo($key, $target, Database::getConnectionInfo('default')['default']);
    $this->assertSame($sql_base->getDatabase()->getTarget(), $target);
    $this->assertSame($sql_base->getDatabase()->getKey(), $key);

    // Verify that setting explicit connection information overrides fallbacks.
    $target = 'test_db_target';
    $key = 'test_migrate_connection';
    $config = ['target' => $target, 'key' => $key];
    $sql_base->setConfiguration($config);
    Database::addConnectionInfo($key, $target, Database::getConnectionInfo('default')['default']);

    // Validate we have injected our custom key and target.
    $this->assertSame($sql_base->getDatabase()->getTarget(), $target);
    $this->assertSame($sql_base->getDatabase()->getKey(), $key);

    // Now test we can have SqlBase create the connection from an info array.
    $sql_base = new TestSqlBase();

    $target = 'test_db_target2';
    $key = 'test_migrate_connection2';
    $database = Database::getConnectionInfo('default')['default'];
    $config = ['target' => $target, 'key' => $key, 'database' => $database];
    $sql_base->setConfiguration($config);

    // Call getDatabase() to get the connection defined.
    $sql_base->getDatabase();

    // Validate the connection has been created with the right values.
    $this->assertSame(Database::getConnectionInfo($key)[$target], $database);

    // Now, test this all works when using state to store db info.
    $target = 'test_state_db_target';
    $key = 'test_state_migrate_connection';
    $config = ['target' => $target, 'key' => $key];
    $database_state_key = 'migrate_sql_base_test';
    \Drupal::state()->set($database_state_key, $config);
    $sql_base->setConfiguration(['database_state_key' => $database_state_key]);
    Database::addConnectionInfo($key, $target, Database::getConnectionInfo('default')['default']);

    // Validate we have injected our custom key and target.
    $this->assertSame($sql_base->getDatabase()->getTarget(), $target);
    $this->assertSame($sql_base->getDatabase()->getKey(), $key);

    // Now test we can have SqlBase create the connection from an info array.
    $sql_base = new TestSqlBase();

    $target = 'test_state_db_target2';
    $key = 'test_state_migrate_connection2';
    $database = Database::getConnectionInfo('default')['default'];
    $config = ['target' => $target, 'key' => $key, 'database' => $database];
    $database_state_key = 'migrate_sql_base_test2';
    \Drupal::state()->set($database_state_key, $config);
    $sql_base->setConfiguration(['database_state_key' => $database_state_key]);

    // Call getDatabase() to get the connection defined.
    $sql_base->getDatabase();

    // Validate the connection has been created with the right values.
    $this->assertSame(Database::getConnectionInfo($key)[$target], $database);

    // Verify that falling back to 'migrate' when the connection is not defined
    // throws a RequirementsException.
    \Drupal::state()->delete('migrate.fallback_state_key');
    $sql_base->setConfiguration([]);
    Database::renameConnection('migrate', 'fallback_connection');
    $this->setExpectedException(RequirementsException::class,
      'No database connection configured for source plugin');
    $sql_base->getDatabase();
  }

}

namespace Drupal\migrate\Plugin\migrate\source;

/**
 * A dummy source to help with testing SqlBase.
 *
 * @package Drupal\migrate\Plugin\migrate\source
 */
class TestSqlBase extends SqlBase {

  /**
   * Overrides the constructor so we can create one easily.
   */
  public function __construct() {
    $this->state = \Drupal::state();
  }

  /**
   * Gets the database without caching it.
   */
  public function getDatabase() {
    $this->database = NULL;
    return parent::getDatabase();
  }

  /**
   * Allows us to set the configuration from a test.
   *
   * @param array $config
   *   The config array.
   */
  public function setConfiguration($config) {
    $this->configuration = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {}

  /**
   * {@inheritdoc}
   */
  public function fields() {}

  /**
   * {@inheritdoc}
   */
  public function query() {}

}

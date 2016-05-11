<?php

namespace Drupal\Core\Test;

use Drupal\Core\Database\ConnectionNotDefinedException;
use Drupal\Core\Database\Database;

/**
 * Provides helper methods for interacting with the Simpletest database.
 */
class TestDatabase {

  /**
   * Returns the database connection to the site running Simpletest.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection to use for inserting assertions.
   *
   * @see \Drupal\simpletest\TestBase::prepareEnvironment()
   */
  public static function getConnection() {
    // Check whether there is a test runner connection.
    // @see run-tests.sh
    // @todo Convert Simpletest UI runner to create + use this connection, too.
    try {
      $connection = Database::getConnection('default', 'test-runner');
    }
    catch (ConnectionNotDefinedException $e) {
      // Check whether there is a backup of the original default connection.
      // @see TestBase::prepareEnvironment()
      try {
        $connection = Database::getConnection('default', 'simpletest_original_default');
      }
      catch (ConnectionNotDefinedException $e) {
        // If TestBase::prepareEnvironment() or TestBase::restoreEnvironment()
        // failed, the test-specific database connection does not exist
        // yet/anymore, so fall back to the default of the (UI) test runner.
        $connection = Database::getConnection('default', 'default');
      }
    }
    return $connection;
  }

}

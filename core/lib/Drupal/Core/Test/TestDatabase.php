<?php

namespace Drupal\Core\Test;

use Drupal\Component\FileSystem\FileSystem;
use Drupal\Core\Database\ConnectionNotDefinedException;
use Drupal\Core\Database\Database;

/**
 * Provides helper methods for interacting with the Simpletest database.
 */
class TestDatabase {

  /**
   * A random number used to ensure that test fixtures are unique to each test
   * method.
   *
   * @var int
   */
  protected $lockId;

  /**
   * The test database prefix.
   *
   * @var string
   */
  protected $databasePrefix;

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

  /**
   * TestDatabase constructor.
   *
   * @param string|null $db_prefix
   *   If not provided a new test lock is generated.
   * @param bool $create_lock
   *   (optional) Whether or not to create a lock file. Defaults to FALSE. If
   *   the environment variable RUN_TESTS_CONCURRENCY is greater than 1 it will
   *   be overridden to TRUE regardless of its initial value.
   *
   * @throws \InvalidArgumentException
   *   Thrown when $db_prefix does not match the regular expression.
   */
  public function __construct($db_prefix = NULL, $create_lock = FALSE) {
    if ($db_prefix === NULL) {
      $this->lockId = $this->getTestLock($create_lock);
      $this->databasePrefix = 'test' . $this->lockId;
    }
    else {
      $this->databasePrefix = $db_prefix;
      // It is possible that we're running a test inside a test. In which case
      // $db_prefix will be something like test12345678test90123456 and the
      // generated lock ID for the running test method would be 90123456.
      preg_match('/test(\d+)$/', $db_prefix, $matches);
      if (!isset($matches[1])) {
        throw new \InvalidArgumentException("Invalid database prefix: $db_prefix");
      }
      $this->lockId = $matches[1];
    }
  }

  /**
   * Gets the relative path to the test site directory.
   *
   * @return string
   *   The relative path to the test site directory.
   */
  public function getTestSitePath() {
    return 'sites/simpletest/' . $this->lockId;
  }

  /**
   * Gets the test database prefix.
   *
   * @return string
   *   The test database prefix.
   */
  public function getDatabasePrefix() {
    return $this->databasePrefix;
  }

  /**
   * Generates a unique lock ID for the test method.
   *
   * @param bool $create_lock
   *   (optional) Whether or not to create a lock file. Defaults to FALSE.
   *
   * @return int
   *   The unique lock ID for the test method.
   */
  protected function getTestLock($create_lock = FALSE) {
    // There is a risk that the generated random number is a duplicate. This
    // would cause different tests to try to use the same database prefix.
    // Therefore, if running with a concurrency of greater than 1, we need to
    // create a lock.
    if (getenv('RUN_TESTS_CONCURRENCY') > 1) {
      $create_lock = TRUE;
    }

    do {
      $lock_id = mt_rand(10000000, 99999999);
      if ($create_lock && @symlink(__FILE__, $this->getLockFile($lock_id)) === FALSE) {
        // If we can't create a symlink, the lock ID is in use. Generate another
        // one. Symlinks are used because they are atomic and reliable.
        $lock_id = NULL;
      }
    } while ($lock_id === NULL);
    return $lock_id;
  }

  /**
   * Releases a lock.
   *
   * @return bool
   *   TRUE if successful, FALSE if not.
   */
  public function releaseLock() {
    return unlink($this->getLockFile($this->lockId));
  }

  /**
   * Releases all test locks.
   *
   * This should only be called once all the test fixtures have been cleaned up.
   */
  public static function releaseAllTestLocks() {
    $tmp = FileSystem::getOsTemporaryDirectory();
    $dir = dir($tmp);
    while (($entry = $dir->read()) !== FALSE) {
      if ($entry === '.' || $entry === '..') {
        continue;
      }
      $entry_path = $tmp . '/' . $entry;
      if (preg_match('/^test_\d+/', $entry) && is_link($entry_path)) {
        unlink($entry_path);
      }
    }
  }

  /**
   * Gets the lock file path.
   *
   * @param int $lock_id
   *   The test method lock ID.
   *
   * @return string
   *   A file path to the symbolic link that prevents the lock ID being re-used.
   */
  protected function getLockFile($lock_id) {
    return FileSystem::getOsTemporaryDirectory() . '/test_' . $lock_id;
  }

}

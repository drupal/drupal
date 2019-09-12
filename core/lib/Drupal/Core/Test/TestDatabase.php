<?php

namespace Drupal\Core\Test;

use Drupal\Component\FileSystem\FileSystem;
use Drupal\Core\Database\ConnectionNotDefinedException;
use Drupal\Core\Database\Database;

/**
 * Provides helper methods for interacting with the fixture database.
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

  /**
   * Store an assertion from outside the testing context.
   *
   * This is useful for inserting assertions that can only be recorded after
   * the test case has been destroyed, such as PHP fatal errors. The caller
   * information is not automatically gathered since the caller is most likely
   * inserting the assertion on behalf of other code. In all other respects
   * the method behaves just like \Drupal\simpletest\TestBase::assert() in terms
   * of storing the assertion.
   *
   * @param string $test_id
   *   The test ID to which the assertion relates.
   * @param string $test_class
   *   The test class to store an assertion for.
   * @param bool|string $status
   *   A boolean or a string of 'pass' or 'fail'. TRUE means 'pass'.
   * @param string $message
   *   The assertion message.
   * @param string $group
   *   The assertion message group.
   * @param array $caller
   *   The an array containing the keys 'file' and 'line' that represent the
   *   file and line number of that file that is responsible for the assertion.
   *
   * @return int
   *   Message ID of the stored assertion.
   *
   * @internal
   */
  public static function insertAssert($test_id, $test_class, $status, $message = '', $group = 'Other', array $caller = []) {
    // Convert boolean status to string status.
    if (is_bool($status)) {
      $status = $status ? 'pass' : 'fail';
    }

    $caller += [
      'function' => 'Unknown',
      'line' => 0,
      'file' => 'Unknown',
    ];

    $assertion = [
      'test_id' => $test_id,
      'test_class' => $test_class,
      'status' => $status,
      'message' => $message,
      'message_group' => $group,
      'function' => $caller['function'],
      'line' => $caller['line'],
      'file' => $caller['file'],
    ];

    return static::getConnection()
      ->insert('simpletest')
      ->fields($assertion)
      ->execute();
  }

  /**
   * Get information about the last test that ran given a test ID.
   *
   * @param int $test_id
   *   The test ID to get the last test from.
   *
   * @return array
   *   Associative array containing the last database prefix used and the
   *   last test class that ran.
   *
   * @internal
   */
  public static function lastTestGet($test_id) {
    $connection = static::getConnection();

    // Define a subquery to identify the latest 'message_id' given the
    // $test_id.
    $max_message_id_subquery = $connection
      ->select('simpletest', 'sub')
      ->condition('test_id', $test_id);
    $max_message_id_subquery->addExpression('MAX(message_id)', 'max_message_id');

    // Run a select query to return 'last_prefix' from {simpletest_test_id} and
    // 'test_class' from {simpletest}.
    $select = $connection->select($max_message_id_subquery, 'st_sub');
    $select->join('simpletest', 'st', 'st.message_id = st_sub.max_message_id');
    $select->join('simpletest_test_id', 'sttid', 'st.test_id = sttid.test_id');
    $select->addField('sttid', 'last_prefix');
    $select->addField('st', 'test_class');
    return $select->execute()->fetchAssoc();
  }

  /**
   * Reads the error log and reports any errors as assertion failures.
   *
   * The errors in the log should only be fatal errors since any other errors
   * will have been recorded by the error handler.
   *
   * @param int $test_id
   *   The test ID to which the log relates.
   * @param string $test_class
   *   The test class to which the log relates.
   *
   * @return bool
   *   Whether any fatal errors were found.
   *
   * @internal
   */
  public function logRead($test_id, $test_class) {
    $log = DRUPAL_ROOT . '/' . $this->getTestSitePath() . '/error.log';
    $found = FALSE;
    if (file_exists($log)) {
      foreach (file($log) as $line) {
        if (preg_match('/\[.*?\] (.*?): (.*?) in (.*) on line (\d+)/', $line, $match)) {
          // Parse PHP fatal errors for example: PHP Fatal error: Call to
          // undefined function break_me() in /path/to/file.php on line 17
          $caller = [
            'line' => $match[4],
            'file' => $match[3],
          ];
          static::insertAssert($test_id, $test_class, FALSE, $match[2], $match[1], $caller);
        }
        else {
          // Unknown format, place the entire message in the log.
          static::insertAssert($test_id, $test_class, FALSE, $line, 'Fatal error');
        }
        $found = TRUE;
      }
    }
    return $found;
  }

  /**
   * Defines the database schema for run-tests.sh and simpletest module.
   *
   * @return array
   *   Array suitable for use in a hook_schema() implementation.
   *
   * @internal
   */
  public static function testingSchema() {
    $schema['simpletest'] = [
      'description' => 'Stores simpletest messages',
      'fields' => [
        'message_id' => [
          'type' => 'serial',
          'not null' => TRUE,
          'description' => 'Primary Key: Unique simpletest message ID.',
        ],
        'test_id' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Test ID, messages belonging to the same ID are reported together',
        ],
        'test_class' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'The name of the class that created this message.',
        ],
        'status' => [
          'type' => 'varchar',
          'length' => 9,
          'not null' => TRUE,
          'default' => '',
          'description' => 'Message status. Core understands pass, fail, exception.',
        ],
        'message' => [
          'type' => 'text',
          'not null' => TRUE,
          'description' => 'The message itself.',
        ],
        'message_group' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'The message group this message belongs to. For example: warning, browser, user.',
        ],
        'function' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'Name of the assertion function or method that created this message.',
        ],
        'line' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Line number on which the function is called.',
        ],
        'file' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'Name of the file where the function is called.',
        ],
      ],
      'primary key' => ['message_id'],
      'indexes' => [
        'reporter' => ['test_class', 'message_id'],
      ],
    ];
    $schema['simpletest_test_id'] = [
      'description' => 'Stores simpletest test IDs, used to auto-increment the test ID so that a fresh test ID is used.',
      'fields' => [
        'test_id' => [
          'type' => 'serial',
          'not null' => TRUE,
          'description' => 'Primary Key: Unique simpletest ID used to group test results together. Each time a set of tests
                            are run a new test ID is used.',
        ],
        'last_prefix' => [
          'type' => 'varchar',
          'length' => 60,
          'not null' => FALSE,
          'default' => '',
          'description' => 'The last database prefix used during testing.',
        ],
      ],
      'primary key' => ['test_id'],
    ];
    return $schema;
  }

  /**
   * Inserts the parsed PHPUnit results into {simpletest}.
   *
   * @param array[] $phpunit_results
   *   An array of test results, as returned from
   *   \Drupal\Core\Test\JUnitConverter::xmlToRows(). These results are in a
   *   form suitable for inserting into the {simpletest} table of the test
   *   results database.
   *
   * @internal
   */
  public static function processPhpUnitResults($phpunit_results) {
    if ($phpunit_results) {
      $query = static::getConnection()
        ->insert('simpletest')
        ->fields(array_keys($phpunit_results[0]));
      foreach ($phpunit_results as $result) {
        $query->values($result);
      }
      $query->execute();
    }
  }

}

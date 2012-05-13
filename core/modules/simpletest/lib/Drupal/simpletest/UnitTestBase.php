<?php

/**
 * @file
 * Definition of Drupal\simpletest\UnitTestBase.
 */

namespace Drupal\simpletest;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\ConnectionNotDefinedException;

/**
 * Test case for Drupal unit tests.
 *
 * These tests can not access the database nor files. Calling any Drupal
 * function that needs the database will throw exceptions. These include
 * watchdog(), module_implements(), module_invoke_all() etc.
 */
abstract class UnitTestBase extends TestBase {

  /**
   * Constructor for UnitTestBase.
   */
  function __construct($test_id = NULL) {
    parent::__construct($test_id);
    $this->skipClasses[__CLASS__] = TRUE;
  }

  /**
   * Sets up unit test environment.
   *
   * Unlike Drupal\simpletest\WebTestBase::setUp(), UnitTestBase::setUp() does not
   * install modules because tests are performed without accessing the database.
   * Any required files must be explicitly included by the child class setUp()
   * method.
   */
  protected function setUp() {
    global $conf;

    // Store necessary current values before switching to the test environment.
    $this->originalFileDirectory = variable_get('file_public_path', conf_path() . '/files');

    // Reset all statics so that test is performed with a clean environment.
    drupal_static_reset();

    // Generate temporary prefixed database to ensure that tests have a clean starting point.
    $this->databasePrefix = Database::getConnection()->prefixTables('{simpletest' . mt_rand(1000, 1000000) . '}');

    // Create test directory.
    $public_files_directory = $this->originalFileDirectory . '/simpletest/' . substr($this->databasePrefix, 10);
    file_prepare_directory($public_files_directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
    $conf['file_public_path'] = $public_files_directory;

    // Clone the current connection and replace the current prefix.
    $connection_info = Database::getConnectionInfo('default');
    Database::renameConnection('default', 'simpletest_original_default');
    foreach ($connection_info as $target => $value) {
      $connection_info[$target]['prefix'] = array(
        'default' => $value['prefix']['default'] . $this->databasePrefix,
      );
    }
    Database::addConnectionInfo('default', 'default', $connection_info['default']);

    // Set user agent to be consistent with web test case.
    $_SERVER['HTTP_USER_AGENT'] = $this->databasePrefix;

    // If locale is enabled then t() will try to access the database and
    // subsequently will fail as the database is not accessible.
    $module_list = module_list();
    if (isset($module_list['locale'])) {
      $this->originalModuleList = $module_list;
      unset($module_list['locale']);
      module_list(TRUE, FALSE, FALSE, $module_list);
    }
    $this->setup = TRUE;
  }

  protected function tearDown() {
    global $conf;

    // Get back to the original connection.
    Database::removeConnection('default');
    Database::renameConnection('simpletest_original_default', 'default');

    $conf['file_public_path'] = $this->originalFileDirectory;
    // Restore modules if necessary.
    if (isset($this->originalModuleList)) {
      module_list(TRUE, FALSE, FALSE, $this->originalModuleList);
    }
  }
}

<?php

/**
 * @file
 * Definition of Drupal\simpletest\UnitTestBase.
 */

namespace Drupal\simpletest;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\ConnectionNotDefinedException;

/**
 * Base test case class for unit tests.
 *
 * These tests can not access the database nor files. Calling any Drupal
 * function that needs the database will throw exceptions. These include
 * watchdog(), Drupal::moduleHandler()->getImplementations(), module_invoke_all() etc.
 */
abstract class UnitTestBase extends TestBase {

  /**
   * @var array
   */
 protected $configDirectories;

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
   * Unlike \Drupal\simpletest\WebTestBase::setUp(), UnitTestBase::setUp() does
   * not install modules because tests are performed without accessing the
   * database. Any required files must be explicitly included by the child class
   * setUp() method.
   */
  protected function setUp() {
    global $conf;

    // Create the database prefix for this test.
    $this->prepareDatabasePrefix();

    // Prepare the environment for running tests.
    $this->prepareEnvironment();
    if (!$this->setupEnvironment) {
      return FALSE;
    }

    // Reset all statics and variables to perform tests in a clean environment.
    $conf = array();
    drupal_static_reset();

    $conf['file_public_path'] = $this->public_files_directory;

    // Change the database prefix.
    // All static variables need to be reset before the database prefix is
    // changed, since \Drupal\Core\Utility\CacheArray implementations attempt to
    // write back to persistent caches when they are destructed.
    $this->changeDatabasePrefix();
    if (!$this->setupDatabasePrefix) {
      return FALSE;
    }

    // Set user agent to be consistent with WebTestBase.
    $_SERVER['HTTP_USER_AGENT'] = $this->databasePrefix;

    $this->setup = TRUE;
  }
}

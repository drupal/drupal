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
 * watchdog(), \Drupal::moduleHandler()->getImplementations(),
 * \Drupal::moduleHandler()->invokeAll() etc.
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
   * Unlike \Drupal\simpletest\WebTestBase::setUp(), UnitTestBase::setUp() does
   * not install modules because tests are performed without accessing the
   * database. Any required files must be explicitly included by the child class
   * setUp() method.
   */
  protected function setUp() {
    file_prepare_directory($this->public_files_directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
    $this->settingsSet('file_public_path', $this->public_files_directory);
  }
}

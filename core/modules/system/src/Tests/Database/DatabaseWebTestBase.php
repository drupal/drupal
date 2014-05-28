<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\DatabaseWebTestBase.
 */

namespace Drupal\system\Tests\Database;

use Drupal\simpletest\WebTestBase;

/**
 * Base class for databases database tests.
 */
abstract class DatabaseWebTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('database_test');

  function setUp() {
    parent::setUp();

    DatabaseTestBase::addSampleData();
  }
}

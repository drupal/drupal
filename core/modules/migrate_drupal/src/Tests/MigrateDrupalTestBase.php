<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\MigrateDrupalTestBase.
 */

namespace Drupal\migrate_drupal\Tests;

use Drupal\migrate\Tests\MigrateTestBase;

/**
 * Base class for Drupal migration tests.
 */
abstract class MigrateDrupalTestBase extends MigrateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('migrate_drupal');

  /**
   * Returns the path to the dump directory.
   *
   * @return string
   *   A string that represents the dump directory path.
   */
  protected function getDumpDirectory() {
    return __DIR__ . '/Dump';
  }

}

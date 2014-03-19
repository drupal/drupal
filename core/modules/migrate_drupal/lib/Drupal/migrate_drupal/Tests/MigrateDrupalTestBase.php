<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\MigrateDrupalTestBase.
 */

namespace Drupal\migrate_drupal\Tests;

use Drupal\migrate\Tests\MigrateTestBase;

class MigrateDrupalTestBase extends MigrateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('migrate_drupal');
}

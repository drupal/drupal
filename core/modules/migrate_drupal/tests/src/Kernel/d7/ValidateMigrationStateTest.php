<?php

namespace Drupal\Tests\migrate_drupal\Kernel\d7;

use Drupal\Tests\DeprecatedModulesTestTrait;
use Drupal\Tests\migrate_drupal\Traits\ValidateMigrationStateTestTrait;

/**
 * Tests the migration state information in module.migrate_drupal.yml.
 *
 * @group migrate_drupal
 */
class ValidateMigrationStateTest extends MigrateDrupal7TestBase {

  use DeprecatedModulesTestTrait;
  use ValidateMigrationStateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Test migrations states.
    'migrate_state_finished_test',
    'migrate_state_not_finished_test',
  ];

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal\Kernel\d7;

use Drupal\Tests\migrate_drupal\Traits\ValidateMigrationStateTestTrait;

/**
 * Tests the migration state information in module.migrate_drupal.yml.
 *
 * @group migrate_drupal
 */
class ValidateMigrationStateTest extends MigrateDrupal7TestBase {

  use ValidateMigrationStateTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Test migrations states.
    'migrate_state_finished_test',
    'migrate_state_not_finished_test',
  ];

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal\Kernel\d6;

use Drupal\Tests\migrate_drupal\Traits\ValidateMigrationStateTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the migration state information in module.migrate_drupal.yml.
 */
#[Group('migrate_drupal')]
#[RunTestsInSeparateProcesses]
class ValidateMigrationStateTest extends MigrateDrupal6TestBase {

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

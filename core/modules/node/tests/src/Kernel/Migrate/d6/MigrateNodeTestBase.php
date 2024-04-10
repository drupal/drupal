<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\user\Entity\User;

/**
 * Base class for Node migration tests.
 */
abstract class MigrateNodeTestBase extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installConfig(['node']);
    $this->installSchema('node', ['node_access']);

    // Create a new user which needs to have UID 1, because that is expected by
    // the assertions from
    // \Drupal\migrate_drupal\Tests\d6\MigrateNodeRevisionTest.
    User::create([
      'uid' => 1,
      'name' => $this->randomMachineName(),
      'status' => 1,
    ])->enforceIsNew()->save();

    $this->migrateUsers(FALSE);
    $this->migrateFields();
    $this->executeMigration('d6_node_settings');
  }

}

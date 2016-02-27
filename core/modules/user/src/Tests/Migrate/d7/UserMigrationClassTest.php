<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Migrate\d7\UserMigrationBuilderTest.
 */

namespace Drupal\user\Tests\Migrate\d7;

use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Tests the user migration plugin class.
 *
 * @group user
 */
class UserMigrationClassTest extends MigrateDrupal7TestBase {

  /**
   * Tests that profile fields are merged into the d6_profile_values migration's
   * process pipeline by the d6_profile_values builder.
   */
  public function testClass() {
    $migration = $this->getMigration('d7_user');
    /** @var \Drupal\migrate\Entity\MigrationInterface[] $migrations */
    $this->assertIdentical('d7_user', $migration->id());
    $process = $migration->getProcess();
    $this->assertIdentical('field_file', $process['field_file'][0]['source']);
  }

}

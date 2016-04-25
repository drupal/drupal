<?php

namespace Drupal\Tests\user\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests the user migration plugin class.
 *
 * @group user
 */
class UserMigrationClassTest extends MigrateDrupal7TestBase {

  /**
   * Tests d6_profile_values builder.
   *
   * Ensures profile fields are merged into the d6_profile_values migration's
   * process pipeline.
   */
  public function testClass() {
    $migration = $this->getMigration('d7_user');
    /** @var \Drupal\migrate\Plugin\MigrationInterface[] $migrations */
    $this->assertIdentical('d7_user', $migration->id());
    $process = $migration->getProcess();
    $this->assertIdentical('field_file', $process['field_file'][0]['source']);
  }

}

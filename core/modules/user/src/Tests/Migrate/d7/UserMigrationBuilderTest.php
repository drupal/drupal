<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Migrate\d7\UserMigrationBuilderTest.
 */

namespace Drupal\user\Tests\Migrate\d7;

use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * @group user
 */
class UserMigrationBuilderTest extends MigrateDrupal7TestBase {

  public static $modules = ['migrate', 'migrate_drupal', 'user'];

  /**
   * Tests that profile fields are merged into the d6_profile_values migration's
   * process pipeline by the d6_profile_values builder.
   */
  public function testBuilder() {
    $template = \Drupal::service('migrate.template_storage')
      ->getTemplateByName('d7_user');
    /** @var \Drupal\migrate\Entity\MigrationInterface[] $migrations */
    $migrations = \Drupal::service('plugin.manager.migrate.builder')
      ->createInstance('d7_user')
      ->buildMigrations($template);

    $this->assertIdentical('d7_user', $migrations[0]->id());
    $process = $migrations[0]->getProcess();
    $this->assertIdentical('field_file', $process['field_file'][0]['source']);
  }

}

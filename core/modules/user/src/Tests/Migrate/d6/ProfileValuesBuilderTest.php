<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Migrate\d6\ProfileValuesBuilderTest.
 */

namespace Drupal\user\Tests\Migrate\d6;

use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * @group user
 */
class ProfileValuesBuilderTest extends MigrateDrupal6TestBase {

  public static $modules = ['migrate', 'migrate_drupal', 'user'];

  /**
   * Tests that profile fields are merged into the d6_profile_values migration's
   * process pipeline by the d6_profile_values builder.
   */
  public function testBuilder() {
    $template = \Drupal::service('migrate.template_storage')
      ->getTemplateByName('d6_profile_values');
    /** @var \Drupal\migrate\Entity\MigrationInterface[] $migrations */
    $migrations = \Drupal::service('plugin.manager.migrate.builder')
      ->createInstance('d6_profile_values')
      ->buildMigrations($template);

    $this->assertIdentical('d6_profile_values', $migrations[0]->id());
    $process = $migrations[0]->getProcess();
    $this->assertIdentical('profile_color', $process['profile_color'][0]['source']);
  }

}

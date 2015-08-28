<?php

/**
 * @file
 * Contains \Drupal\image\Tests\Migrate\d7\MigrateImageSettingsTest.
 */

namespace Drupal\image\Tests\Migrate\d7;

use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of Image variables to configuration.
 *
 * @group image
 */
class MigrateImageSettingsTest extends MigrateDrupal7TestBase {

  public static $modules = ['image'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d7_image_settings');
  }

  /**
   * Tests the migration.
   */
  public function testMigration() {
    $config = $this->config('image.settings');
    // These settings are not recommended...
    $this->assertTrue($config->get('allow_insecure_derivatives'));
    $this->assertTrue($config->get('suppress_itok_output'));
    $this->assertIdentical("core/modules/image/testsample.png",$config->get('preview_image'));
  }

}

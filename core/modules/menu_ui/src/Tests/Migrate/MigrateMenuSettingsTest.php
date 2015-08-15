<?php

/**
 * @file
 * Contains \Drupal\menu_ui\Tests\Migrate\MigrateMenuSettingsTest.
 */

namespace Drupal\menu_ui\Tests\Migrate;

use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of menu_ui settings.
 *
 * @group menu_ui
 */
class MigrateMenuSettingsTest extends MigrateDrupal7TestBase {

  public static $modules = ['menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['menu_ui']);
    $this->executeMigration('menu_settings');
  }

  public function testMigration() {
    $this->assertTrue(\Drupal::config('menu_ui.settings')->get('override_parent_selector'));
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_ui\Kernel\Migrate;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of menu_ui settings.
 *
 * @group menu_ui
 */
class MigrateMenuSettingsTest extends MigrateDrupal7TestBase {

  protected static $modules = ['menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['menu_ui']);
    $this->executeMigration('menu_settings');
  }

  public function testMigration(): void {
    $this->assertTrue(\Drupal::config('menu_ui.settings')->get('override_parent_selector'));
  }

}

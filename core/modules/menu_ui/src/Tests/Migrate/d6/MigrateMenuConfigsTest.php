<?php

/**
 * @file
 * Contains \Drupal\menu_ui\Tests\Migrate\d6\MigrateMenuConfigsTest.
 */

namespace Drupal\menu_ui\Tests\Migrate\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to menu_ui.settings.yml.
 *
 * @group menu_ui
 */
class MigrateMenuConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('menu_ui');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadDumps(['Variable.php']);
    $this->executeMigration('d6_menu_settings');
  }

  /**
   * Tests migration of variables for the Menu UI module.
   */
  public function testMenuSettings() {
    $config = $this->config('menu_ui.settings');
    $this->assertIdentical(FALSE, $config->get('override_parent_selector'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'menu_ui.settings', $config->get());
  }

}

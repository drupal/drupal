<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateMenuConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Upgrade variables to menu_ui.settings.yml.
 *
 * @group migrate_drupal
 */
class MigrateMenuConfigsTest extends MigrateDrupalTestBase {

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
    $migration = entity_load('migration', 'd6_menu_settings');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6MenuSettings.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests migration of variables for the Menu UI module.
   */
  public function testMenuSettings() {
    $config = $this->config('menu_ui.settings');
    $this->assertIdentical($config->get('override_parent_selector'), FALSE);
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'menu_ui.settings', $config->get());
  }

}

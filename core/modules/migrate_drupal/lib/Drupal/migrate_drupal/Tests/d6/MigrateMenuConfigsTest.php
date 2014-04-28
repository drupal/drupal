<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateMenuConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests migration of variables for the Menu UI module.
 */
class MigrateMenuConfigsTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('menu_ui');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate variables to menu_ui.settings.yml',
      'description'  => 'Upgrade variables to menu_ui.settings.yml',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
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
    $config = \Drupal::config('menu_ui.settings');
    $this->assertIdentical($config->get('main_links'), 'primary-links');
    $this->assertIdentical($config->get('secondary_links'), 'secondary-links');
    $this->assertIdentical($config->get('override_parent_selector'), FALSE);
  }
}

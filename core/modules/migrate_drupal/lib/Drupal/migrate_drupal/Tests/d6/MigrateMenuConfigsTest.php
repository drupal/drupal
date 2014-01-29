<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateMenuConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests migration of variables for the Menu module.
 */
class MigrateMenuConfigsTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('menu');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate variables to menu.settings.yml',
      'description'  => 'Upgrade variables to menu.settings.yml',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * Tests migration of forum variables to forum.settings.yml.
   */
  public function testMenuSettings() {
    $migration = entity_load('migration', 'd6_menu_settings');
    $dumps = array(
      drupal_get_path('module', 'migrate_drupal') . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6MenuSettings.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    $config = \Drupal::config('menu.settings');
    $this->assertIdentical($config->get('main_links'), 'primary-links');
    $this->assertIdentical($config->get('secondary_links'), 'secondary-links');
    $this->assertIdentical($config->get('override_parent_selector'), FALSE);
  }
}

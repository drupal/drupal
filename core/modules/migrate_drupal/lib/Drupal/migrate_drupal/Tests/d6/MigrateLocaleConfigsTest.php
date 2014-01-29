<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateLocaleConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests migration of variables from the Locale module.
 */
class MigrateLocaleConfigsTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('locale');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate variables to locale.settings.yml',
      'description'  => 'Upgrade variables to locale.settings.yml',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * Tests migration of locale variables to locale.settings.yml.
   */
  public function testLocaleSettings() {
    $migration = entity_load('migration', 'd6_locale_settings');
    $dumps = array(
      drupal_get_path('module', 'migrate_drupal') . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6LocaleSettings.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    $config = \Drupal::config('locale.settings');
    $this->assertIdentical($config->get('cache_string'), 1);
    $this->assertIdentical($config->get('javascript.directory'), 'languages');
  }

}

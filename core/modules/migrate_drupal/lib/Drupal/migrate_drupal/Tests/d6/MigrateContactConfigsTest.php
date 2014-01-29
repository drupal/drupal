<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateContactConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests migration of variables from the Contact module.
 */
class MigrateContactConfigsTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('contact');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate variables to contact.settings',
      'description'  => 'Upgrade variables to contact.settings.yml',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * Tests migration of aggregator variables to aggregator.settings.yml.
   */
  public function testContactSettings() {
    $migration = entity_load('migration', 'd6_contact_settings');
    $dumps = array(
      drupal_get_path('module', 'migrate_drupal') . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6ContactSettings.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    $config = \Drupal::config('contact.settings');
    $this->assertIdentical($config->get('user_default_enabled'), true);
    $this->assertIdentical($config->get('flood.limit'), 3);
  }
}

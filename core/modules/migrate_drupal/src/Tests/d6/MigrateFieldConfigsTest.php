<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateFieldConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests migration of variables from the Field module.
 */
class MigrateFieldConfigsTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate variables to field.settings.yml',
      'description'  => 'Upgrade variables to field.settings.yml',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_field_settings');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6FieldSettings.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests migration of field variables to field.settings.yml.
   */
  public function testFieldSettings() {
    $config = \Drupal::config('field.settings');
    $this->assertIdentical($config->get('language_fallback'), TRUE);
  }
}

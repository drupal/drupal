<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateActionConfigSchemaTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests the language config schema.
 */
class MigrateActionConfigSchemaTest extends MigrateDrupalTestBase {

 use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('action');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate action configuration schema',
      'description'  => 'Tests the configuration schema of action module',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_action_settings');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6ActionSettings.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests migration of action variables to action.settings.yml.
   */
  public function testActionConfigSchema() {
    $config_data = \Drupal::config('action.settings')->get();
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'action.settings', $config_data);
  }

}

<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSystemSiteTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests Drupal 6 node settings to Drupal 8 migration.
 */
class MigrateNodeConfigsTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate variables to node.settings.yml',
      'description'  => 'Upgrade variables to node.settings.yml',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_node_settings');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6NodeSettings.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage);
    $executable->import();
  }

  /**
   * Tests Drupal 6 node settings to Drupal 8 migration.
   */
  public function testNodeSettings() {
    $config = \Drupal::config('node.settings');
    $this->assertIdentical($config->get('use_admin_theme'), false);
  }
}

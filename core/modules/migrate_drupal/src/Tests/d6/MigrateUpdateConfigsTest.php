<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUpdateConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Upgrade variables to update.settings.yml.
 *
 * @group migrate_drupal
 */
class MigrateUpdateConfigsTest extends MigrateDrupalTestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('update');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_update_settings');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6UpdateSettings.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests migration of update variables to update.settings.yml.
   */
  public function testUpdateSettings() {
    $config = \Drupal::config('update.settings');
    $this->assertIdentical($config->get('fetch.max_attempts'), 2);
    $this->assertIdentical($config->get('fetch.url'), 'http://updates.drupal.org/release-history');
    $this->assertIdentical($config->get('notification.threshold'), 'all');
    $this->assertIdentical($config->get('notification.emails'), array());
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'update.settings', $config->get());
  }

}

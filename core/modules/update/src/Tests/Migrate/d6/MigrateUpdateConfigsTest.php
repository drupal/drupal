<?php

/**
 * @file
 * Contains \Drupal\update\Tests\Migrate\d6\MigrateUpdateConfigsTest.
 */

namespace Drupal\update\Tests\Migrate\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to update.settings.yml.
 *
 * @group update
 */
class MigrateUpdateConfigsTest extends MigrateDrupal6TestBase {

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
    $this->loadDumps(['Variable.php']);
    $this->executeMigration('d6_update_settings');
  }

  /**
   * Tests migration of update variables to update.settings.yml.
   */
  public function testUpdateSettings() {
    $config = $this->config('update.settings');
    $this->assertIdentical(2, $config->get('fetch.max_attempts'));
    $this->assertIdentical('http://updates.drupal.org/release-history', $config->get('fetch.url'));
    $this->assertIdentical('all', $config->get('notification.threshold'));
    $this->assertIdentical(array(), $config->get('notification.emails'));
    $this->assertIdentical(7, $config->get('check.interval_days'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'update.settings', $config->get());
  }

}

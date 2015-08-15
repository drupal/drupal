<?php

/**
 * @file
 * Contains \Drupal\tracker\Tests\Migrate\d7\MigrateTrackerSettingsTest.
 */

namespace Drupal\tracker\Tests\Migrate\d7;

use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of Tracker settings to configuration.
 *
 * @group tracker
 */
class MigrateTrackerSettingsTest extends MigrateDrupal7TestBase {

  public static $modules = ['tracker'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['tracker']);
    $this->executeMigration('d7_tracker_settings');
  }

  /**
   * Tests migration of tracker's variables to configuration.
   */
  public function testMigration() {
    $this->assertIdentical(999, \Drupal::config('tracker.settings')->get('cron_index_limit'));
  }

}

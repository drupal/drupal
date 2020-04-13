<?php

namespace Drupal\Tests\tracker\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of Tracker settings to configuration.
 *
 * @group tracker
 */
class MigrateTrackerSettingsTest extends MigrateDrupal7TestBase {

  protected static $modules = ['tracker'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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

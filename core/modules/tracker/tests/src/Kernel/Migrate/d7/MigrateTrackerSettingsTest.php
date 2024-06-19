<?php

declare(strict_types=1);

namespace Drupal\Tests\tracker\Kernel\Migrate\d7;

/**
 * Tests migration of Tracker settings to configuration.
 *
 * @group tracker
 * @group legacy
 */
class MigrateTrackerSettingsTest extends MigrateDrupalTestBase {

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
  public function testMigration(): void {
    $this->assertSame(999, \Drupal::config('tracker.settings')->get('cron_index_limit'));
  }

}

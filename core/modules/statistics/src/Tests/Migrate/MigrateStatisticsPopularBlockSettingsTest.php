<?php

/**
 * @file
 * Contains \Drupal\statistics\Tests\Migrate\MigrateStatisticsPopularBlockSettingsTest.
 */

namespace Drupal\statistics\Tests\Migrate;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of settings for the Popular block.
 *
 * @group statistics
 */
class MigrateStatisticsPopularBlockSettingsTest extends MigrateDrupal7TestBase {

  use SchemaCheckTestTrait;

  public static $modules = ['statistics'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['statistics']);
    $this->loadDumps(['Variable.php']);
    $this->executeMigration('statistics_popular_block_settings');
  }

  /**
   * Tests migration of Popular block settings into configuration.
   */
  public function testMigration() {
    $config = \Drupal::config('block.settings.statistics_popular_block')->get();
    $this->assertIdentical(55, $config['top_all_num']);
    $this->assertIdentical(30, $config['top_day_num']);
    $this->assertIdentical(9, $config['top_last_num']);
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'block.settings.statistics_popular_block', $config);
  }

}

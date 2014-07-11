<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateStatisticsConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Upgrade variables to statistics.settings.yml.
 *
 * @group migrate_drupal
 */
class MigrateStatisticsConfigsTest extends MigrateDrupalTestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('statistics');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_statistics_settings');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6StatisticsSettings.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

  /**
   * Tests migration of statistics variables to statistics.settings.yml.
   */
  public function testStatisticsSettings() {
    $config = \Drupal::config('statistics.settings');
    $this->assertIdentical($config->get('access_log.enabled'), FALSE);
    $this->assertIdentical($config->get('access_log.max_lifetime'), 259200);
    $this->assertIdentical($config->get('count_content_views'), 0);
    $this->assertIdentical($config->get('block.popular.top_day_limit'), 0);
    $this->assertIdentical($config->get('block.popular.top_all_limit'), 0);
    $this->assertIdentical($config->get('block.popular.top_recent_limit'), 0);
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'statistics.settings', $config->get());
  }

}

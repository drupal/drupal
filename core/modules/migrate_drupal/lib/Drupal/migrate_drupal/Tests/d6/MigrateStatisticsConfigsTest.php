<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateStatisticsConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests migration of variables from the Statistics module.
 */
class MigrateStatisticsConfigsTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('statistics');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate variables to statistics.settings.yml',
      'description'  => 'Upgrade variables to statistics.settings.yml',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * Tests migration of statistics variables to statistics.settings.yml.
   */
  public function testStatisticsSettings() {
    $migration = entity_load('migration', 'd6_statistics_settings');
    $dumps = array(
      drupal_get_path('module', 'migrate_drupal') . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6StatisticsSettings.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    $config = \Drupal::config('statistics.settings');
    $this->assertIdentical($config->get('access_log.enable'), 0);
    $this->assertIdentical($config->get('access_log.max_lifetime'), 259200);
    $this->assertIdentical($config->get('count_content_views'), NULL);
    $this->assertIdentical($config->get('block.popular.top_day_limit'), 0);
    $this->assertIdentical($config->get('block.popular.top_all_limit'), 0);
    $this->assertIdentical($config->get('block.popular.top_recent_limit'), 0);
  }
}

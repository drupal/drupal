<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Upgrade\MigrateSystemSiteTest.
 */

namespace Drupal\migrate_drupal\Tests;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;

class MigrateSystemConfigsTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate variables to system.*.yml',
      'description'  => 'Upgrade variables to system.*.yml',
      'group' => 'Migrate Drupal',
    );
  }

  function testSystemSite() {
    $migration = entity_load('migration', 'd6_system_site');
    $dumps = array(
      drupal_get_path('module', 'migrate_drupal') . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SystemSite.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage);
    $executable->import();
    $config = \Drupal::config('system.site');
    $this->assertIdentical($config->get('name'), 'drupal');
    $this->assertIdentical($config->get('mail'), 'admin@example.com');
    $this->assertIdentical($config->get('slogan'), 'Migrate rocks');
    $this->assertIdentical($config->get('page.front'), 'anonymous-hp');
    $this->assertIdentical($config->get('page.403'), 'user');
    $this->assertIdentical($config->get('page.404'), 'page-not-found');
    $this->assertIdentical($config->get('weight_select_max'), 99);
    $this->assertIdentical($config->get('admin_compact_mode'), FALSE);
  }

  /**
   * Tests migration of system (cron) variables to system.cron.yml.
   */
  public function testSystemCron() {
    $migration = entity_load('migration', 'd6_system_cron');
    $dumps = array(
      drupal_get_path('module', 'migrate_drupal') . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SystemCron.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    $config = \Drupal::config('system.cron');
    $this->assertIdentical($config->get('threshold.warning'), 172800);
    $this->assertIdentical($config->get('threshold.error'), 1209600);
  }

  /**
   * Tests migration of system (rss) variables to system.rss.yml.
   */
  public function testSystemRss() {
    $migration = entity_load('migration', 'd6_system_rss');
    $dumps = array(
      drupal_get_path('module', 'migrate_drupal') . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SystemRss.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    $config = \Drupal::config('system.rss');
    $this->assertIdentical($config->get('items.limit'), 10);
  }

  /**
   * Tests migration of system (Performance) variables to system.performance.yml.
   */
  public function testSystemPerformance() {
    $migration = entity_load('migration', 'd6_system_performance');
    $dumps = array(
      drupal_get_path('module', 'migrate_drupal') . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SystemPerformance.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    $config = \Drupal::config('system.performance');
    $this->assertIdentical($config->get('css.preprocess'), 0);
    $this->assertIdentical($config->get('js.preprocess'), 0);
    $this->assertIdentical($config->get('cache.page.max_age'), 0);
  }
}

<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSystemSiteTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

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
    $this->assertIdentical($config->get('css.preprocess'), false);
    $this->assertIdentical($config->get('js.preprocess'), false);
    $this->assertIdentical($config->get('cache.page.max_age'), 0);
  }

  /**
   * Tests migration of system (theme) variables to system.theme.yml.
   */
  public function testSystemTheme() {
    $migration = entity_load('migration', 'd6_system_theme');
    $dumps = array(
      drupal_get_path('module', 'migrate_drupal') . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SystemTheme.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    $config = \Drupal::config('system.theme');
    $this->assertIdentical($config->get('admin'), '0');
    $this->assertIdentical($config->get('default'), 'garland');
  }

  /**
   * Tests migration of system (maintenance) variables to system.maintenance.yml.
   */
  public function testSystemMaintenance() {
    $migration = entity_load('migration', 'd6_system_maintenance');
    $dumps = array(
      drupal_get_path('module', 'migrate_drupal') . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SystemMaintenance.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    $config = \Drupal::config('system.maintenance');
    $this->assertIdentical($config->get('enable'), 0);
    $this->assertIdentical($config->get('message'), 'Drupal is currently under maintenance. We should be back shortly. Thank you for your patience.');
  }

  /**
   * Tests migration of system (site) variables to system.site.yml.
   */
  public function testSystemSite() {
    $migration = entity_load('migration', 'd6_system_site');
    $dumps = array(
      drupal_get_path('module', 'migrate_drupal') . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SystemSite.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    $config = \Drupal::config('system.site');
    $this->assertIdentical($config->get('name'), 'site_name');
    $this->assertIdentical($config->get('mail'), 'site_mail@example.com');
    $this->assertIdentical($config->get('slogan'), 'Migrate rocks');
    $this->assertIdentical($config->get('page.403'), 'user');
    $this->assertIdentical($config->get('page.404'), 'page-not-found');
    $this->assertIdentical($config->get('page.front'), 'node');
    $this->assertIdentical($config->get('admin_compact_mode'), FALSE);
  }

  /**
   * Tests migration of system (filter) variables to system.filter.yml.
   */
  public function testSystemFilter() {
    $migration = entity_load('migration', 'd6_system_filter');
    $dumps = array(
      drupal_get_path('module', 'migrate_drupal') . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SystemFilter.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    $config = \Drupal::config('system.filter');
    $this->assertIdentical($config->get('protocols'), array('http', 'https', 'ftp', 'news', 'nntp', 'tel', 'telnet', 'mailto', 'irc', 'ssh', 'sftp', 'webcal', 'rtsp'));
  }

  /**
   * Tests migration of system (image) variables to system.image.yml.
   */
  public function testSystemImage() {
    $migration = entity_load('migration', 'd6_system_image');
    $dumps = array(
      drupal_get_path('module', 'migrate_drupal') . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SystemImage.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    $config = \Drupal::config('system.image');
    $this->assertIdentical($config->get('toolkit'), 'gd');
  }

  /**
   * Tests migration of system (image GD) variables to system.image.gd.yml.
   */
  public function testSystemImageGd() {
    $migration = entity_load('migration', 'd6_system_image_gd');
    $dumps = array(
      drupal_get_path('module', 'migrate_drupal') . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SystemImageGd.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    $config = \Drupal::config('system.image.gd');
    $this->assertIdentical($config->get('jpeg_quality'), 75);
  }

  /**
   * Tests migration of system (file) variables to system.file.yml.
   */
  public function testSystemFile() {
    $migration = entity_load('migration', 'd6_system_file');
    $dumps = array(
      drupal_get_path('module', 'migrate_drupal') . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SystemFile.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    $old_state = \Drupal::configFactory()->getOverrideState();
    \Drupal::configFactory()->setOverrideState(FALSE);
    $config = \Drupal::config('system.file');
    $this->assertIdentical($config->get('path.private'), 'files/test');
    $this->assertIdentical($config->get('path.temporary'), 'files/temp');
    \Drupal::configFactory()->setOverrideState($old_state);
  }

}

<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSyslogConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests migration of variables from the Syslog module.
 */
class MigrateSyslogConfigsTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('syslog');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate variables to syslog.settings.yml',
      'description'  => 'Upgrade variables to syslog.settings.yml',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * Tests migration of syslog variables to syslog.settings.yml.
   */
  public function testSyslogSettings() {
    $migration = entity_load('migration', 'd6_syslog_settings');
    $dumps = array(
      drupal_get_path('module', 'migrate_drupal') . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SyslogSettings.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    $config = \Drupal::config('syslog.settings');
    $this->assertIdentical($config->get('identity'), 'drupal');
    // @TODO: change this to integer once there's schema of this config.
    $this->assertIdentical($config->get('facility'), '128');
  }
}

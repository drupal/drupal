<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSyslogConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_syslog_settings');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6SyslogSettings.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests migration of syslog variables to syslog.settings.yml.
   */
  public function testSyslogSettings() {
    $config = \Drupal::config('syslog.settings');
    $this->assertIdentical($config->get('identity'), 'drupal');
    $this->assertIdentical($config->get('facility'), '128');
  }
}

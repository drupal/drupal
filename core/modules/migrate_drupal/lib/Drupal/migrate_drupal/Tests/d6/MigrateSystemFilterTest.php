<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSystemFilterTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests the Drupal 6 system filter variables to Drupal 8 system.filter config migration.
 */
class MigrateSystemFilterTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate filter variables to system.*.yml',
      'description'  => 'Upgrade filter variables to system.*.yml',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_system_filter');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6SystemFilter.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

  /**
   * Tests migration of system (filter) variables to system.filter.yml.
   */
  public function testSystemFilter() {
    $config = \Drupal::config('system.filter');
    $this->assertIdentical($config->get('protocols'), array('http', 'https', 'ftp', 'news', 'nntp', 'tel', 'telnet', 'mailto', 'irc', 'ssh', 'sftp', 'webcal', 'rtsp'));
  }

}

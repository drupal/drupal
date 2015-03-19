<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSystemFilterTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade filter variables to system.*.yml.
 *
 * @group migrate_drupal
 */
class MigrateSystemFilterTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_system_filter');
    $dumps = array(
      $this->getDumpDirectory() . '/Variable.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

  /**
   * Tests migration of system (filter) variables to system.filter.yml.
   */
  public function testSystemFilter() {
    $config = $this->config('system.filter');
    $this->assertIdentical(array('http', 'https', 'ftp', 'news', 'nntp', 'tel', 'telnet', 'mailto', 'irc', 'ssh', 'sftp', 'webcal', 'rtsp'), $config->get('protocols'));
  }

}

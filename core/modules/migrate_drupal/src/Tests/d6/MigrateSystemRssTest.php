<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSystemRssTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Upgrade rss variable to system.*.yml.
 *
 * @group migrate_drupal
 */
class MigrateSystemRssTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_system_rss');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6SystemRss.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

  /**
   * Tests migration of system (rss) variables to system.rss.yml.
   */
  public function testSystemRss() {
    $config = $this->config('system.rss');
    $this->assertIdentical($config->get('items.limit'), 10);
    $this->assertIdentical($config->get('items.view_mode'), 'title');
  }

}

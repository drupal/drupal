<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSystemRssTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

/**
 * Upgrade rss variable to system.*.yml.
 *
 * @group migrate_drupal
 */
class MigrateSystemRssTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadDumps(['Variable.php']);
    $this->executeMigration('d6_system_rss');
  }

  /**
   * Tests migration of system (rss) variables to system.rss.yml.
   */
  public function testSystemRss() {
    $config = $this->config('system.rss');
    $this->assertIdentical(10, $config->get('items.limit'));
    $this->assertIdentical('title', $config->get('items.view_mode'));
  }

}

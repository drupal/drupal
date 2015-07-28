<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Migrate\d6\MigrateSystemRssTest.
 */

namespace Drupal\system\Tests\Migrate\d6;

use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade rss variable to system.*.yml.
 *
 * @group system
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

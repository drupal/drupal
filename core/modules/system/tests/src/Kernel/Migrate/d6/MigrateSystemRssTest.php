<?php

namespace Drupal\Tests\system\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade rss variable to system.*.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateSystemRssTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('system_rss');
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

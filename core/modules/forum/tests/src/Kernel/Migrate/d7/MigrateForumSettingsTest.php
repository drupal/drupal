<?php

namespace Drupal\Tests\forum\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of Forum's variables to configuration.
 *
 * @group forum
 */
class MigrateForumSettingsTest extends MigrateDrupal7TestBase {

  // Don't alphabetize these. They're in dependency order.
  protected static $modules = [
    'comment',
    'field',
    'filter',
    'text',
    'node',
    'taxonomy',
    'forum',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('d7_taxonomy_vocabulary');
    $this->executeMigration('d7_forum_settings');
  }

  /**
   * Tests the migration of Forum's settings to configuration.
   */
  public function testForumSettingsMigration() {
    $config = $this->config('forum.settings');
    $this->assertSame(9, $config->get('block.active.limit'));
    $this->assertSame(4, $config->get('block.new.limit'));
    $this->assertSame(10, $config->get('topics.hot_threshold'));
    $this->assertSame(25, $config->get('topics.page_limit'));
    $this->assertSame(1, $config->get('topics.order'));
    $this->assertSame('forums', $config->get('vocabulary'));
  }

}

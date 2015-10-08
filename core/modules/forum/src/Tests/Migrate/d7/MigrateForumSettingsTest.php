<?php

/**
 * @file
 * Contains \Drupal\forum\Tests\d7\MigrateForumSettingsTest.
 */

namespace Drupal\forum\Tests\Migrate\d7;

use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of Forum's variables to configuration.
 *
 * @group forum
 */
class MigrateForumSettingsTest extends MigrateDrupal7TestBase {

  // Don't alphabetize these. They're in dependency order.
  public static $modules = [
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
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d7_taxonomy_vocabulary');
    $this->executeMigration('d7_forum_settings');
  }

  /**
   * Tests the migration of Forum's settings to configuration.
   */
  public function testForumSettingsMigration() {
    $config = $this->config('forum.settings');
    $this->assertIdentical(9, $config->get('block.active.limit'));
    $this->assertIdentical(4, $config->get('block.new.limit'));
    $this->assertIdentical(10, $config->get('topics.hot_threshold'));
    $this->assertIdentical(25, $config->get('topics.page_limit'));
    $this->assertIdentical(1, $config->get('topics.order'));
    $this->assertIdentical('forums', $config->get('vocabulary'));
  }

}

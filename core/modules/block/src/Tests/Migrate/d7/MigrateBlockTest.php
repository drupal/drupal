<?php

/**
 * @file
 * Contains \Drupal\block\Tests\Migrate\d7\MigrateBlockTest.
 */

namespace Drupal\block\Tests\Migrate\d7;

use Drupal\block\Entity\Block;
use Drupal\block_content\Entity\BlockContent;
use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of blocks to configuration entities.
 *
 * @group block
 */
class MigrateBlockTest extends MigrateDrupal7TestBase {

 /**
   * {@inheritdoc}
   */
  static $modules = [
    'block',
    'views',
    'comment',
    'menu_ui',
    'block_content',
    'node',
    'text',
    'filter',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(static::$modules);
    $this->installEntitySchema('block_content');

    // Set Bartik and Seven as the default public and admin theme.
    $config = $this->config('system.theme');
    $config->set('default', 'bartik');
    $config->set('admin', 'seven');
    $config->save();

    // Install one of D8's test themes.
    \Drupal::service('theme_handler')->install(['bartik']);

    $this->executeMigrations([
      'd7_filter_format',
      'd7_user_role',
      'block_content_type',
      'block_content_body_field',
      'd7_custom_block',
      'd7_block',
    ]);
  }

  /**
   * Asserts various aspects of a block.
   *
   * @param string $id
   *   The block ID.
   * @param string $plugin_id
   *   The block's plugin ID.
   * @param array $roles
   *   Role IDs the block is expected to have.
   * @param string $pages
   *   The list of pages on which the block should appear.
   * @param string $region
   *   The display region.
   * @param string $theme
   *   The theme.
   * @param string $weight
   *   The block weight.
   */
  public function assertEntity($id, $plugin_id, array $roles, $pages, $region, $theme, $weight) {
    $block = Block::load($id);
    $this->assertTrue($block instanceof Block);
    /** @var \Drupal\block\BlockInterface $block */
    $this->assertIdentical($plugin_id, $block->getPluginId());

    $visibility = $block->getVisibility();
    if ($roles) {
      $this->assertIdentical($roles, array_values($visibility['user_role']['roles']));
      $this->assertIdentical('@user.current_user_context:current_user', $visibility['user_role']['context_mapping']['user']);
    }
    if ($pages) {
      $this->assertIdentical($pages, $visibility['request_path']['pages']);
    }

    $this->assertIdentical($region, $block->getRegion());
    $this->assertIdentical($theme, $block->getTheme());
    $this->assertIdentical($weight, $block->getWeight());
  }

  /**
   * Tests the block migration.
   */
  public function testBlockMigration() {
    $this->assertEntity('bartik_system_main', 'system_main_block', [], '', 'content', 'bartik', 0);
    $this->assertEntity('bartik_search_form', 'search_form_block', [], '', 'sidebar_first', 'bartik', -1);
    $this->assertEntity('bartik_user_login', 'user_login_block', [], '', 'sidebar_first', 'bartik', 0);
    $this->assertEntity('bartik_system_powered-by', 'system_powered_by_block', [], '', 'footer', 'bartik', 10);
    $this->assertEntity('seven_system_main', 'system_main_block', [], '', 'content', 'seven', 0);
    $this->assertEntity('seven_user_login', 'user_login_block', [], '', 'content', 'seven', 10);

    // The d7_custom_block migration should have migrated a block containing a
    // mildly amusing limerick. We'll need its UUID to determine
    // bartik_block_1's plugin ID.
    $uuid = BlockContent::load(1)->uuid();
    $this->assertEntity('bartik_block_1', 'block_content:' . $uuid, ['authenticated'], '', 'highlighted', 'bartik', 0);

    // Assert that disabled blocks (or enabled blocks whose plugin IDs could
    // be resolved) did not migrate.
    $non_existent_blocks = [
      'bartik_system_navigation',
      'bartik_system_help',
      'seven_user_new',
      'seven_search_form',
      'bartik_comment_recent',
      'bartik_node_syndicate',
      'bartik_node_recent',
      'bartik_shortcut_shortcuts',
      'bartik_system_management',
      'bartik_system_user-menu',
      'bartik_system_main-menu',
      'bartik_user_new',
      'bartik_user_online',
      'seven_comment_recent',
      'seven_node_syndicate',
      'seven_shortcut_shortcuts',
      'seven_system_powered-by',
      'seven_system_navigation',
      'seven_system_management',
      'seven_system_user-menu',
      'seven_system_main-menu',
      'seven_user_online',
      'bartik_blog_recent',
      'bartik_book_navigation',
      'bartik_locale_language',
      'bartik_forum_active',
      'bartik_forum_new',
      'seven_blog_recent',
      'seven_book_navigation',
      'seven_locale_language',
      'seven_forum_active',
      'seven_forum_new',
      'bartik_menu_menu-test-menu',
      'bartik_statistics_popular',
      'seven_menu_menu-test-menu',
      'seven_statistics_popular',
      'seven_block_1',
    ];
    $this->assertTrue(empty(Block::loadMultiple($non_existent_blocks)));
  }

}

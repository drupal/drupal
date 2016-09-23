<?php

namespace Drupal\Tests\block\Kernel\Migrate\d6;

use Drupal\block\Entity\Block;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests migration of blocks to configuration entities.
 *
 * @group migrate_drupal_6
 */
class MigrateBlockTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'views',
    'comment',
    'menu_ui',
    'block_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['block_content']);
    $this->installEntitySchema('block_content');

    // Set Bartik and Seven as the default public and admin theme.
    $config = $this->config('system.theme');
    $config->set('default', 'bartik');
    $config->set('admin', 'seven');
    $config->save();

    // Install one of D8's test themes.
    \Drupal::service('theme_handler')->install(['test_theme']);

    $this->executeMigrations([
      'd6_filter_format',
      'block_content_type',
      'block_content_body_field',
      'd6_custom_block',
      'menu',
      'd6_user_role',
      'd6_block',
    ]);
    block_rebuild();
  }

  /**
   * Asserts various aspects of a block.
   *
   * @param string $id
   *   The block ID.
   * @param array $visibility
   *   The block visibility settings.
   * @param string $region
   *   The display region.
   * @param string $theme
   *   The theme.
   * @param string $weight
   *   The block weight.
   * @param string $label
   *   The block label.
   * @param string $label_display
   *   The block label display setting.
   * @param bool $status
   *   (optional) Whether the block is expected to be enabled.
   */
  public function assertEntity($id, $visibility, $region, $theme, $weight, $label, $label_display, $status = TRUE) {
    $block = Block::load($id);
    $this->assertTrue($block instanceof Block);
    $this->assertSame($visibility, $block->getVisibility());
    $this->assertSame($region, $block->getRegion());
    $this->assertSame($theme, $block->getTheme());
    $this->assertSame($weight, $block->getWeight());
    $this->assertSame($status, $block->status());

    $config = $this->config('block.block.' . $id);
    $this->assertSame($label, $config->get('settings.label'));
    $this->assertSame($label_display, $config->get('settings.label_display'));
  }

  /**
   * Tests the block migration.
   */
  public function testBlockMigration() {
    $blocks = Block::loadMultiple();
    $this->assertIdentical(9, count($blocks));

    // User blocks
    $visibility = [];
    $visibility['request_path']['id'] = 'request_path';
    $visibility['request_path']['negate'] = TRUE;
    $visibility['request_path']['pages'] = "<front>\n/node/1\n/blog/*";
    $this->assertEntity('user', $visibility, 'sidebar_first', 'bartik', 0, '', '0');

    $visibility = [];
    $this->assertEntity('user_1', $visibility, 'sidebar_first', 'bartik', 0, '', '0');

    $visibility['user_role']['id'] = 'user_role';
    $roles['authenticated'] = 'authenticated';
    $visibility['user_role']['roles'] = $roles;
    $context_mapping['user'] = '@user.current_user_context:current_user';
    $visibility['user_role']['context_mapping'] = $context_mapping;
    $visibility['user_role']['negate'] = FALSE;
    $this->assertEntity('user_2', $visibility, 'sidebar_second', 'bartik', -9, '', '0');

    $visibility = [];
    $visibility['user_role']['id'] = 'user_role';
    $visibility['user_role']['roles'] = [
      'migrate_test_role_1' => 'migrate_test_role_1'
    ];
    $context_mapping['user'] = '@user.current_user_context:current_user';
    $visibility['user_role']['context_mapping'] = $context_mapping;
    $visibility['user_role']['negate'] = FALSE;
    $this->assertEntity('user_3', $visibility, 'sidebar_second', 'bartik', -6, '', '0');

    // Check system block
    $visibility = [];
    $visibility['request_path']['id'] = 'request_path';
    $visibility['request_path']['negate'] = TRUE;
    $visibility['request_path']['pages'] = '/node/1';
    $this->assertEntity('system', $visibility, 'footer_fifth', 'bartik', -5, '', '0');

    // Check menu blocks
    $visibility = [];
    $this->assertEntity('menu', $visibility, 'header', 'bartik', -5, '', '0');

    // Check custom blocks
    $visibility['request_path']['id'] = 'request_path';
    $visibility['request_path']['negate'] = FALSE;
    $visibility['request_path']['pages'] = '<front>';
    $this->assertEntity('block', $visibility, 'content', 'bartik', 0, 'Static Block', 'visible');

    $visibility['request_path']['id'] = 'request_path';
    $visibility['request_path']['negate'] = FALSE;
    $visibility['request_path']['pages'] = '/node';
    // bluemarine does not exist in Drupal 8 and the d6_block migration defines
    // no mapping for its regions, so this block should have been defaulted
    // to the 'content' region.
    $this->assertEntity('block_1', $visibility, 'content', 'bluemarine', -4, 'Another Static Block', 'visible');

    $visibility = [];
    $this->assertEntity('block_2', $visibility, 'right', 'test_theme', -7, '', '0');

    // Custom block with php code is not migrated.
    $block = Block::load('block_3');
    $this->assertFalse($block instanceof Block);
  }

}

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
  protected static $modules = [
    'block',
    'views',
    'comment',
    'menu_ui',
    'block_content',
    'taxonomy',
    'node',
    'forum',
    'path_alias',
    'statistics',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install the themes used for this test.
    $this->installEntitySchema('block_content');
    $this->container->get('theme_installer')->install(['olivero', 'test_theme']);

    $this->installConfig(['block_content']);

    // Set Olivero as the default public theme.
    $config = $this->config('system.theme');
    $config->set('default', 'olivero');
    $config->save();

    $this->executeMigrations([
      'd6_filter_format',
      'block_content_type',
      'block_content_body_field',
      'd6_custom_block',
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
   * @param int $weight
   *   The block weight.
   * @param array $settings
   *   (optional) The block settings.
   * @param bool $status
   *   Whether the block is expected to be enabled or disabled.
   *
   * @internal
   */
  public function assertEntity(string $id, array $visibility, string $region, string $theme, int $weight, array $settings = NULL, bool $status = TRUE): void {
    $block = Block::load($id);
    $this->assertInstanceOf(Block::class, $block);
    $this->assertSame($visibility, $block->getVisibility());
    $this->assertSame($region, $block->getRegion());
    $this->assertSame($theme, $block->getTheme());
    $this->assertSame($weight, $block->getWeight());
    $this->assertSame($status, $block->status());
    if ($settings) {
      $block_settings = $block->get('settings');
      $block_settings['id'] = current(explode(':', $block_settings['id']));
      $this->assertEquals($settings, $block_settings);
    }
  }

  /**
   * Tests the block migration.
   */
  public function testBlockMigration() {
    $blocks = Block::loadMultiple();
    $this->assertCount(25, $blocks);

    // Check user blocks.
    $visibility = [
      'request_path' => [
        'id' => 'request_path',
        'negate' => TRUE,
        'pages' => "<front>\n/node/1\n/blog/*",
      ],
    ];
    $settings = [
      'id' => 'user_login_block',
      'label' => '',
      'provider' => 'user',
      'label_display' => '0',
    ];
    $this->assertEntity('user', $visibility, 'sidebar', 'olivero', -10, $settings);

    $visibility = [];
    $settings = [
      'id' => 'system_menu_block',
      'label' => 'zu - Navigation',
      'provider' => 'system',
      'label_display' => 'visible',
      'level' => 1,
      'expand_all_items' => FALSE,
      'depth' => 0,
    ];
    $this->assertEntity('user_1', $visibility, 'sidebar', 'olivero', -11, $settings);

    $visibility = [
      'user_role' => [
        'id' => 'user_role',
        'negate' => FALSE,
        'context_mapping' => [
          'user' => '@user.current_user_context:current_user',
        ],
        'roles' => [
          'authenticated' => 'authenticated',
        ],
      ],
    ];
    $settings = [
      'id' => 'broken',
      'label' => '',
      'provider' => 'core',
      'label_display' => '0',
      'items_per_page' => '5',
    ];
    $this->assertEntity('user_2', $visibility, 'sidebar', 'olivero', -11, $settings);

    $visibility = [
      'user_role' => [
        'id' => 'user_role',
        'negate' => FALSE,
        'context_mapping' => [
          'user' => '@user.current_user_context:current_user',
        ],
        'roles' => [
          'migrate_test_role_1' => 'migrate_test_role_1',
        ],
      ],
    ];
    $settings = [
      'id' => 'broken',
      'label' => '',
      'provider' => 'core',
      'label_display' => '0',
      'items_per_page' => '10',
    ];
    $this->assertEntity('user_3', $visibility, 'sidebar', 'olivero', -10, $settings);

    // Check system block.
    $visibility = [
      'request_path' => [
        'id' => 'request_path',
        'negate' => TRUE,
        'pages' => '/node/1',
      ],
    ];
    $settings = [
      'id' => 'system_powered_by_block',
      'label' => '',
      'provider' => 'system',
      'label_display' => '0',
    ];
    $this->assertEntity('system', $visibility, 'footer_top', 'olivero', -5, $settings);

    // Check menu blocks.
    $settings = [
      'id' => 'broken',
      'label' => '',
      'provider' => 'core',
      'label_display' => '0',
    ];
    $this->assertEntity('menu', [], 'header', 'olivero', -5, $settings);

    // Check aggregator block.
    $settings = [
      'id' => 'broken',
      'label' => '',
      'provider' => 'core',
      'label_display' => '0',
      'block_count' => 7,
      'feed' => '5',
    ];
    $this->assertEntity('aggregator', [], 'sidebar', 'olivero', -2, $settings);

    // Check book block.
    $settings = [
      'id' => 'broken',
      'label' => '',
      'provider' => 'core',
      'label_display' => '0',
      'block_mode' => 'book pages',
    ];
    $this->assertEntity('book', [], 'sidebar', 'olivero', -4, $settings);

    // Check forum block settings.
    $settings = [
      'id' => 'forum_active_block',
      'label' => '',
      'provider' => 'forum',
      'label_display' => '0',
      'block_count' => 3,
      'properties' => [
        'administrative' => '1',
      ],
    ];
    $this->assertEntity('forum', [], 'sidebar', 'olivero', -8, $settings);

    $settings = [
      'id' => 'forum_new_block',
      'label' => '',
      'provider' => 'forum',
      'label_display' => '0',
      'block_count' => 4,
      'properties' => [
        'administrative' => '1',
      ],
    ];
    $this->assertEntity('forum_1', [], 'sidebar', 'olivero', -9, $settings);

    // Check statistic block settings.
    $settings = [
      'id' => 'broken',
      'label' => '',
      'provider' => 'core',
      'label_display' => '0',
      'top_day_num' => 7,
      'top_all_num' => 8,
      'top_last_num' => 9,
    ];
    $this->assertEntity('statistics', [], 'sidebar', 'olivero', 0, $settings);

    // Check content blocks.
    $visibility = [
      'request_path' => [
        'id' => 'request_path',
        'negate' => FALSE,
        'pages' => '<front>',
      ],
    ];
    $settings = [
      'id' => 'block_content',
      'label' => 'Static Block',
      'provider' => 'block_content',
      'label_display' => 'visible',
      'status' => TRUE,
      'info' => '',
      'view_mode' => 'full',
    ];
    $this->assertEntity('block', $visibility, 'content', 'olivero', 0, $settings);

    $visibility = [
      'request_path' => [
        'id' => 'request_path',
        'negate' => FALSE,
        'pages' => '/node',
      ],
    ];
    $settings = [
      'id' => 'block_content',
      'label' => 'Another Static Block',
      'provider' => 'block_content',
      'label_display' => 'visible',
      'status' => TRUE,
      'info' => '',
      'view_mode' => 'full',
    ];
    // We expect this block to be disabled because '' is not a valid region,
    // and block_rebuild() will disable any block in an invalid region.
    $this->assertEntity('block_1', $visibility, '', 'bluemarine', -4, $settings, FALSE);

    $settings = [
      'id' => 'block_content',
      'label' => '',
      'provider' => 'block_content',
      'label_display' => '0',
      'status' => TRUE,
      'info' => '',
      'view_mode' => 'full',
    ];
    $this->assertEntity('block_2', [], 'right', 'test_theme', -7, $settings);

    // Content block with php code is not migrated.
    $block = Block::load('block_3');
    $this->assertNotInstanceOf(Block::class, $block);

    // Check migrate messages.
    $messages = iterator_to_array($this->getMigration('d6_block')->getIdMap()->getMessages());
    $this->assertCount(4, $messages);
    $this->assertSame($messages[0]->message, 'Schema errors for block.block.block_1 with the following errors: 0 [dependencies.theme.0] Theme &#039;bluemarine&#039; is not installed.');
    $this->assertSame($messages[1]->message, "d6_block:visibility: The block with bid '13' from module 'block' will have no PHP or request_path visibility configuration.");
    $this->assertSame($messages[2]->message, 'Schema errors for block.block.aggregator with the following errors: block.block.aggregator:settings.block_count missing schema, block.block.aggregator:settings.feed missing schema, 0 [settings.block_count] &#039;block_count&#039; is not a supported key., 1 [settings.feed] &#039;feed&#039; is not a supported key.');
    $this->assertSame($messages[3]->message, 'Schema errors for block.block.book with the following errors: block.block.book:settings.block_mode missing schema, 0 [settings.block_mode] &#039;block_mode&#039; is not a supported key.');
  }

}

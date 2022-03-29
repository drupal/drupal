<?php

namespace Drupal\Tests\aggregator\Kernel\Migrate\d6;

use Drupal\block\Entity\Block;

/**
 * Tests migration of aggregator block.
 *
 * @group aggregator
 * @group legacy
 */
class MigrateBlockTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'block_content',
    'filter',
    'node',
    'path_alias',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install the themes used for this test.
    $this->container->get('theme_installer')->install(['bartik', 'test_theme']);

    $this->installEntitySchema('block_content');
    $this->installConfig(['block_content']);

    // Set Bartik as the default public theme.
    $config = $this->config('system.theme');
    $config->set('default', 'bartik');
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
    $this->assertCount(14, $blocks);

    // Check aggregator block.
    $settings = [
      'id' => 'aggregator_feed_block',
      'label' => '',
      'provider' => 'aggregator',
      'label_display' => '0',
      'block_count' => 7,
      'feed' => '5',
    ];
    $this->assertEntity('aggregator', [], 'sidebar_second', 'bartik', -2, $settings);
  }

}

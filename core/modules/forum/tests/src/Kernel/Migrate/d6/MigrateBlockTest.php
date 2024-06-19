<?php

declare(strict_types=1);

namespace Drupal\Tests\forum\Kernel\Migrate\d6;

use Drupal\block\Entity\Block;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests migration of forum blocks.
 *
 * @group forum
 */
class MigrateBlockTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'block_content',
    'comment',
    'forum',
    'node',
    'path_alias',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('path_alias');

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
   * Gets the path to the fixture file.
   */
  protected function getFixtureFilePath() {
    return __DIR__ . '/../../../../fixtures/drupal6.php';
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
  public function assertEntity(string $id, array $visibility, string $region, string $theme, int $weight, array $settings = [], bool $status = TRUE): void {
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
  public function testBlockMigration(): void {

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
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Kernel;

use Drupal\block\Entity\Block;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\block\Hook\BlockHooks;

/**
 * Tests block_rebuild().
 *
 * @group block
 */
class BlockRebuildTest extends KernelTestBase {

  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'system'];

  /**
   * {@inheritdoc}
   */
  protected static $configSchemaCheckerExclusions = [
    // These blocks are intentionally put into invalid regions, so they will
    // violate config schema.
    // @see ::testRebuildInvalidBlocks()
    'block.block.invalid_block1',
    'block.block.invalid_block2',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->get('theme_installer')->install(['stark']);
    $this->container->get('config.factory')->getEditable('system.theme')->set('default', 'stark')->save();
  }

  /**
   * @covers \Drupal\block\Hook\BlockHooks::rebuild
   */
  public function testRebuildNoBlocks(): void {
    $blockRebuild = new BlockHooks();
    $blockRebuild->rebuild();
    $messages = \Drupal::messenger()->all();
    \Drupal::messenger()->deleteAll();
    $this->assertEquals([], $messages);
  }

  /**
   * @covers \Drupal\block\Hook\BlockHooks::rebuild
   */
  public function testRebuildNoInvalidBlocks(): void {
    $this->placeBlock('system_powered_by_block', ['region' => 'content']);

    $blockRebuild = new BlockHooks();
    $blockRebuild->rebuild();
    $messages = \Drupal::messenger()->all();
    \Drupal::messenger()->deleteAll();
    $this->assertEquals([], $messages);
  }

  /**
   * @covers \Drupal\block\Hook\BlockHooks::rebuild
   */
  public function testRebuildInvalidBlocks(): void {
    $this->placeBlock('system_powered_by_block', ['region' => 'content']);
    $block1 = $this->placeBlock('system_powered_by_block', [
      'id' => 'invalid_block1',
    ]);
    $block2 = $this->placeBlock('system_powered_by_block', [
      'id' => 'invalid_block2',
    ]);
    $block2->disable()->save();
    // Use the config API directly to bypass Block::preSave().
    \Drupal::configFactory()->getEditable('block.block.' . $block1->id())->set('region', 'INVALID')->save();
    \Drupal::configFactory()->getEditable('block.block.' . $block2->id())->set('region', 'INVALID')->save();

    // Reload block entities.
    $block1 = Block::load($block1->id());
    $block2 = Block::load($block2->id());

    $this->assertSame('INVALID', $block1->getRegion());
    $this->assertTrue($block1->status());
    $this->assertSame('INVALID', $block2->getRegion());
    $this->assertFalse($block2->status());

    $blockRebuild = new BlockHooks();
    $blockRebuild->rebuild();

    // Reload block entities.
    $block1 = Block::load($block1->id());
    $block2 = Block::load($block2->id());

    $messages = \Drupal::messenger()->all();
    \Drupal::messenger()->deleteAll();
    $expected = [
      'warning' => [
        new TranslatableMarkup('The block %info was assigned to the invalid region %region and has been disabled.', [
          '%info' => $block1->id(),
          '%region' => 'INVALID',
        ]),
      ],
    ];
    $this->assertEquals($expected, $messages);

    $default_region = system_default_region('stark');
    $this->assertSame($default_region, $block1->getRegion());
    $this->assertFalse($block1->status());
    $this->assertSame($default_region, $block2->getRegion());
    $this->assertFalse($block2->status());
  }

}

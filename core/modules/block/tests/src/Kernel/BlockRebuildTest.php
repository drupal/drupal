<?php

namespace Drupal\Tests\block\Kernel;

use Drupal\block\Entity\Block;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\simpletest\BlockCreationTrait;

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
  public static $modules = ['block', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->container->get('theme_installer')->install(['stable', 'classy']);
    $this->container->get('config.factory')->getEditable('system.theme')->set('default', 'classy')->save();
  }

  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass() {
    parent::setUpBeforeClass();

    // @todo Once block_rebuild() is refactored to auto-loadable code, remove
    //   this require statement.
    require_once static::getDrupalRoot() . '/core/modules/block/block.module';
  }

  /**
   * @covers ::block_rebuild
   */
  public function testRebuildNoBlocks() {
    block_rebuild();
    $messages = \Drupal::messenger()->all();
    \Drupal::messenger()->deleteAll();
    $this->assertEquals([], $messages);
  }

  /**
   * @covers ::block_rebuild
   */
  public function testRebuildNoInvalidBlocks() {
    $this->placeBlock('system_powered_by_block', ['region' => 'content']);

    block_rebuild();
    $messages = \Drupal::messenger()->all();
    \Drupal::messenger()->deleteAll();
    $this->assertEquals([], $messages);
  }

  /**
   * @covers ::block_rebuild
   */
  public function testRebuildInvalidBlocks() {
    $this->placeBlock('system_powered_by_block', ['region' => 'content']);
    $block1 = $this->placeBlock('system_powered_by_block');
    $block2 = $this->placeBlock('system_powered_by_block');
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

    block_rebuild();

    // Reload block entities.
    $block1 = Block::load($block1->id());
    $block2 = Block::load($block2->id());

    $messages = \Drupal::messenger()->all();
    \Drupal::messenger()->deleteAll();
    $expected = ['warning' => [new TranslatableMarkup('The block %info was assigned to the invalid region %region and has been disabled.', ['%info' => $block1->id(), '%region' => 'INVALID'])]];
    $this->assertEquals($expected, $messages);

    $default_region = system_default_region('classy');
    $this->assertSame($default_region, $block1->getRegion());
    $this->assertFalse($block1->status());
    $this->assertSame($default_region, $block2->getRegion());
    $this->assertFalse($block2->status());
  }

}

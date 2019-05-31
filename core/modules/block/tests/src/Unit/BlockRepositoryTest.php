<?php

/**
 * @file
 * Contains \Drupal\Tests\block\Unit\BlockRepositoryTest.
 */

namespace Drupal\Tests\block\Unit;

use Drupal\block\BlockRepository;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\block\BlockRepository
 * @group block
 */
class BlockRepositoryTest extends UnitTestCase {

  /**
   * @var \Drupal\block\BlockRepository
   */
  protected $blockRepository;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $blockStorage;

  /**
   * @var string
   */
  protected $theme;

  /**
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $contextHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $active_theme = $this->getMockBuilder('Drupal\Core\Theme\ActiveTheme')
      ->disableOriginalConstructor()
      ->getMock();
    $this->theme = $this->randomMachineName();
    $active_theme->expects($this->atLeastOnce())
      ->method('getName')
      ->willReturn($this->theme);
    $active_theme->expects($this->atLeastOnce())
      ->method('getRegions')
      ->willReturn([
        'top',
        'center',
        'bottom',
      ]);

    $theme_manager = $this->createMock('Drupal\Core\Theme\ThemeManagerInterface');
    $theme_manager->expects($this->atLeastOnce())
      ->method('getActiveTheme')
      ->will($this->returnValue($active_theme));

    $this->contextHandler = $this->createMock('Drupal\Core\Plugin\Context\ContextHandlerInterface');
    $this->blockStorage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit_Framework_MockObject_MockObject $entity_type_manager */
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->any())
      ->method('getStorage')
      ->willReturn($this->blockStorage);

    $this->blockRepository = new BlockRepository($entity_type_manager, $theme_manager, $this->contextHandler);
  }

  /**
   * Tests the retrieval of block entities.
   *
   * @covers ::getVisibleBlocksPerRegion
   *
   * @dataProvider providerBlocksConfig
   */
  public function testGetVisibleBlocksPerRegion(array $blocks_config, array $expected_blocks) {
    $blocks = [];
    foreach ($blocks_config as $block_id => $block_config) {
      $block = $this->createMock('Drupal\block\BlockInterface');
      $block->expects($this->once())
        ->method('access')
        ->will($this->returnValue($block_config[0]));
      $block->expects($block_config[0] ? $this->atLeastOnce() : $this->never())
        ->method('getRegion')
        ->willReturn($block_config[1]);
      $block->expects($this->any())
        ->method('label')
        ->willReturn($block_id);
      $block->expects($this->any())
        ->method('getWeight')
        ->willReturn($block_config[2]);
      $blocks[$block_id] = $block;
    }

    $this->blockStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['theme' => $this->theme])
      ->willReturn($blocks);
    $result = [];
    $cacheable_metadata = [];
    foreach ($this->blockRepository->getVisibleBlocksPerRegion($cacheable_metadata) as $region => $resulting_blocks) {
      $result[$region] = [];
      foreach ($resulting_blocks as $plugin_id => $block) {
        $result[$region][] = $plugin_id;
      }
    }
    $this->assertEquals($expected_blocks, $result);
  }

  public function providerBlocksConfig() {
    $blocks_config = [
      'block1' => [
        AccessResult::allowed(), 'top', 0,
      ],
      // Test a block without access.
      'block2' => [
        AccessResult::forbidden(), 'bottom', 0,
      ],
      // Test some blocks in the same region with specific weight.
      'block4' => [
        AccessResult::allowed(), 'bottom', 5,
      ],
      'block3' => [
        AccessResult::allowed(), 'bottom', 5,
      ],
      'block5' => [
        AccessResult::allowed(), 'bottom', -5,
      ],
    ];

    $test_cases = [];
    $test_cases[] = [$blocks_config,
      [
        'top' => ['block1'],
        'center' => [],
        'bottom' => ['block5', 'block3', 'block4'],
      ],
    ];
    return $test_cases;
  }

  /**
   * Tests the retrieval of block entities that are context-aware.
   *
   * @covers ::getVisibleBlocksPerRegion
   */
  public function testGetVisibleBlocksPerRegionWithContext() {
    $block = $this->createMock('Drupal\block\BlockInterface');
    $block->expects($this->once())
      ->method('access')
      ->willReturn(AccessResult::allowed()->addCacheTags(['config:block.block.block_id']));
    $block->expects($this->once())
      ->method('getRegion')
      ->willReturn('top');
    $blocks['block_id'] = $block;

    $this->blockStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['theme' => $this->theme])
      ->willReturn($blocks);
    $result = [];
    $cacheable_metadata = [];
    foreach ($this->blockRepository->getVisibleBlocksPerRegion($cacheable_metadata) as $region => $resulting_blocks) {
      $result[$region] = [];
      foreach ($resulting_blocks as $plugin_id => $block) {
        $result[$region][] = $plugin_id;
      }
    }
    $expected = [
      'top' => [
        'block_id',
      ],
      'center' => [],
      'bottom' => [],
    ];
    $this->assertSame($expected, $result);

    // Assert that the cacheable metadata from the block access results was
    // collected.
    $this->assertEquals(['config:block.block.block_id'], $cacheable_metadata['top']->getCacheTags());
  }

}

interface TestContextAwareBlockInterface extends BlockPluginInterface, ContextAwarePluginInterface {
}

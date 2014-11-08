<?php

/**
 * @file
 * Contains \Drupal\Tests\block\Unit\BlockRepositoryTest.
 */

namespace Drupal\Tests\block\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\block\BlockRepository
 * @group block
 */
class BlockRepositoryTest extends UnitTestCase {

  /**
   * Tests the retrieval of block entities.
   *
   * @covers ::getVisibleBlocksPerRegion
   *
   * @dataProvider providerBlocksConfig
   */
  function testGetVisibleBlocksPerRegion(array $blocks_config, array $expected_blocks) {
    $theme = $this->randomMachineName();
    $active_theme = $this->getMockBuilder('Drupal\Core\Theme\ActiveTheme')
      ->disableOriginalConstructor()
      ->getMock();
    $active_theme->expects($this->atLeastOnce())
      ->method('getName')
      ->willReturn($theme);
    $theme_manager = $this->getMock('Drupal\Core\Theme\ThemeManagerInterface');
    $theme_manager->expects($this->once())
      ->method('getActiveTheme')
      ->will($this->returnValue($active_theme));

    $block_storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $entity_manager->expects($this->any())
      ->method('getStorage')
      ->willReturn($block_storage);

    $block_repository = $this->getMockBuilder('Drupal\block\BlockRepository')
      ->setConstructorArgs([$entity_manager, $theme_manager])
      ->setMethods(['getRegionNames'])
      ->getMock();
    $block_repository->expects($this->once())
      ->method('getRegionNames')
      ->willReturn([
        'top' => 'Top',
        'center' => 'Center',
        'bottom' => 'Bottom',
      ]);

    $blocks = [];
    foreach ($blocks_config as $block_id => $block_config) {
      $block = $this->getMock('Drupal\block\BlockInterface');
      $block->expects($this->once())
        ->method('access')
        ->will($this->returnValue($block_config[0]));
      $block->expects($block_config[0] ? $this->atLeastOnce() : $this->never())
        ->method('get')
        ->will($this->returnValueMap(array(
          array('region', $block_config[1]),
          array('weight', $block_config[2]),
          array('status', TRUE),
        )));
      $blocks[$block_id] = $block;
    }

    $block_storage->expects($this->once())
      ->method('loadByProperties')
      ->with(['theme' => $theme])
      ->willReturn($blocks);
    $result = [];
    foreach ($block_repository->getVisibleBlocksPerRegion() as $region => $resulting_blocks) {
      $result[$region] = [];
      foreach ($resulting_blocks as $plugin_id => $block) {
        $result[$region][] = $plugin_id;
      }
    }
    $this->assertSame($result, $expected_blocks);
  }


  public function providerBlocksConfig() {
    $blocks_config = array(
      'block1' => array(
        TRUE, 'top', 0
      ),
      // Test a block without access.
      'block2' => array(
        FALSE, 'bottom', 0
      ),
      // Test two blocks in the same region with specific weight.
      'block3' => array(
        TRUE, 'bottom', 5
      ),
      'block4' => array(
        TRUE, 'bottom', -5
      ),
    );

    $test_cases = [];
    $test_cases[] = [$blocks_config,
      [
        'top' => ['block1'],
        'center' => [],
        'bottom' => ['block4', 'block3'],
      ]
    ];
    return $test_cases;
  }

}

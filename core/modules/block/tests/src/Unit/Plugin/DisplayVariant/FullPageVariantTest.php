<?php

/**
 * @file
 * Contains \Drupal\Tests\block\Unit\Plugin\DisplayVariant\FullPageVariantTest.
 */

namespace Drupal\Tests\block\Unit\Plugin\DisplayVariant;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\block\Plugin\DisplayVariant\FullPageVariant
 * @group block
 */
class FullPageVariantTest extends UnitTestCase {

  /**
   * The block storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $blockStorage;

  /**
   * The block view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $blockViewBuilder;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $routeMatch;

  /**
   * The theme negotiator.
   *
   * @var \Drupal\Core\Theme\ThemeNegotiatorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $themeNegotiator;

  /**
   * Sets up a display variant plugin for testing.
   *
   * @param array $configuration
   *   An array of plugin configuration.
   * @param array $definition
   *   The plugin definition array.
   *
   * @return \Drupal\block\Plugin\DisplayVariant\FullPageVariant|\PHPUnit_Framework_MockObject_MockObject
   *   A mocked display variant plugin.
   */
  public function setUpDisplayVariant($configuration = array(), $definition = array()) {
    $this->blockStorage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $this->blockViewBuilder = $this->getMock('Drupal\Core\Entity\EntityViewBuilderInterface');
    $this->routeMatch = $this->getMock('Drupal\Core\Routing\RouteMatchInterface');
    $this->themeNegotiator = $this->getMock('Drupal\Core\Theme\ThemeNegotiatorInterface');
    return $this->getMockBuilder('Drupal\block\Plugin\DisplayVariant\FullPageVariant')
      ->setConstructorArgs(array($configuration, 'test', $definition, $this->blockStorage, $this->blockViewBuilder, $this->routeMatch, $this->themeNegotiator))
      ->setMethods(array('getRegionNames'))
      ->getMock();
  }

  /**
   * Tests the building of a full page variant.
   *
   * @covers ::build
   * @covers ::getRegionAssignments
   */
  public function testBuild() {
    $theme = $this->randomMachineName();
    $display_variant = $this->setUpDisplayVariant();
    $this->themeNegotiator->expects($this->any())
      ->method('determineActiveTheme')
      ->with($this->routeMatch)
      ->will($this->returnValue($theme));
    $display_variant->expects($this->once())
      ->method('getRegionNames')
      ->will($this->returnValue(array(
        'top' => 'Top',
        'bottom' => 'Bottom',
      )));

    $blocks_config = array(
      'block1' => array(
        TRUE, 'top', 0,
      ),
      // Test a block without access.
      'block2' => array(
        FALSE, 'bottom', 0,
      ),
      // Test two blocks in the same region with specific weight.
      'block3' => array(
        TRUE, 'bottom', 5,
      ),
      'block4' => array(
        TRUE, 'bottom', -5,
      ),
    );
    $blocks = array();
    foreach ($blocks_config as $block_id => $block_config) {
      $block = $this->getMock('Drupal\block\BlockInterface');
      $block->expects($this->once())
        ->method('access')
        ->will($this->returnValue($block_config[0]));
      $block->expects($this->any())
        ->method('get')
        ->will($this->returnValueMap(array(
          array('region', $block_config[1]),
          array('weight', $block_config[2]),
          array('status', TRUE),
        )));
      $blocks[$block_id] = $block;
    }

    $this->blockViewBuilder->expects($this->exactly(3))
      ->method('view')
      ->will($this->returnValue(array()));
    $this->blockStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(array('theme' => $theme))
      ->will($this->returnValue($blocks));

    $expected = array(
      'top' => array(
        'block1' => array(),
        '#sorted' => TRUE,
      ),
      'bottom' => array(
        'block4' => array(),
        'block3' => array(),
        '#sorted' => TRUE,
      ),
    );
    $this->assertSame($expected, $display_variant->build());
  }

}

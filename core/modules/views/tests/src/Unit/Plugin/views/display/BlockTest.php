<?php

namespace Drupal\Tests\views\Unit\Plugin\views\display;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\views\Plugin\views\display\Block
 * @group block
 */
class BlockTest extends UnitTestCase {

  /**
   * The view executable.
   *
   * @var \Drupal\views\ViewExecutable|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $executable;

  /**
   * The views block plugin.
   *
   * @var \Drupal\views\Plugin\Block\ViewsBlock|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $blockPlugin;

  /**
   * The tested block display plugin.
   *
   * @var \Drupal\views\Plugin\views\display\Block|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $blockDisplay;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->setMethods(['executeDisplay', 'setDisplay', 'setItemsPerPage'])
      ->getMock();
    $this->executable->expects($this->any())
      ->method('setDisplay')
      ->with('block_1')
      ->will($this->returnValue(TRUE));

    $this->blockDisplay = $this->executable->display_handler = $this->getMockBuilder('Drupal\views\Plugin\views\display\Block')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();

    $this->blockDisplay->view = $this->executable;

    $this->blockPlugin = $this->getMockBuilder('Drupal\views\Plugin\Block\ViewsBlock')
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Tests the build method with no overriding.
   */
  public function testBuildNoOverride() {
    $this->executable->expects($this->never())
      ->method('setItemsPerPage');

    $this->blockPlugin->expects($this->once())
      ->method('getConfiguration')
      ->will($this->returnValue(['items_per_page' => 'none']));

    $this->blockDisplay->preBlockBuild($this->blockPlugin);
  }

  /**
   * Tests the build method with overriding items per page.
   */
  public function testBuildOverride() {
    $this->executable->expects($this->once())
      ->method('setItemsPerPage')
      ->with(5);

    $this->blockPlugin->expects($this->once())
      ->method('getConfiguration')
      ->will($this->returnValue(['items_per_page' => 5]));

    $this->blockDisplay->preBlockBuild($this->blockPlugin);
  }

}

<?php

declare(strict_types=1);

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
   * @var \Drupal\views\ViewExecutable|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $executable;

  /**
   * The views block plugin.
   *
   * @var \Drupal\views\Plugin\Block\ViewsBlock|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $blockPlugin;

  /**
   * The tested block display plugin.
   *
   * @var \Drupal\views\Plugin\views\display\Block|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $blockDisplay;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->onlyMethods(['executeDisplay', 'setDisplay', 'setItemsPerPage'])
      ->getMock();
    $this->executable->expects($this->any())
      ->method('setDisplay')
      ->with('block_1')
      ->willReturn(TRUE);

    $this->blockDisplay = $this->executable->display_handler = $this->getMockBuilder('Drupal\views\Plugin\views\display\Block')
      ->disableOriginalConstructor()
      ->onlyMethods([])
      ->getMock();

    $this->blockDisplay->view = $this->executable;

    $this->blockPlugin = $this->getMockBuilder('Drupal\views\Plugin\Block\ViewsBlock')
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Tests the build method with no overriding.
   *
   * @testWith [null]
   *           ["none"]
   *           [0]
   * @todo Delete the last two cases in https://www.drupal.org/project/drupal/issues/3521221. The last one is `intval('none')`.
   */
  public function testBuildNoOverride($items_per_page_setting): void {
    $this->executable->expects($this->never())
      ->method('setItemsPerPage');

    $this->blockPlugin->expects($this->once())
      ->method('getConfiguration')
      ->willReturn(['items_per_page' => $items_per_page_setting]);

    $this->blockDisplay->preBlockBuild($this->blockPlugin);
  }

  /**
   * Tests the build method with overriding items per page.
   *
   * @testWith [5, 5]
   *           ["5", 5]
   */
  public function testBuildOverride(mixed $input, int $expected): void {
    $this->executable->expects($this->once())
      ->method('setItemsPerPage')
      ->with($expected);

    $this->blockPlugin->expects($this->once())
      ->method('getConfiguration')
      ->willReturn(['items_per_page' => $input]);

    $this->blockDisplay->preBlockBuild($this->blockPlugin);
  }

}

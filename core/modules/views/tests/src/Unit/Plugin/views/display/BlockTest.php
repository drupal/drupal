<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin\views\display;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\views\Plugin\views\display\Block;
use Drupal\views\ViewExecutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests Drupal\views\Plugin\views\display\Block.
 */
#[CoversClass(Block::class)]
#[Group('block')]
class BlockTest extends UnitTestCase {

  /**
   * The view executable.
   */
  protected ViewExecutable&MockObject $executable;

  /**
   * The views block plugin.
   */
  protected ViewsBlock&MockObject $blockPlugin;

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

    $this->executable = $this->getMockBuilder(ViewExecutable::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['executeDisplay', 'setDisplay', 'setItemsPerPage'])
      ->getMock();
    $this->executable
      ->method('setDisplay')
      ->with('block_1')
      ->willReturn(TRUE);

    $this->blockDisplay = $this->executable->display_handler = new Block([], 'test_plugin', [], $this->createStub(EntityTypeManagerInterface::class), $this->createStub(BlockManagerInterface::class));

    $this->blockDisplay->view = $this->executable;

    $this->blockPlugin = $this->createMock(ViewsBlock::class);
  }

  /**
   * Tests the build method with no overriding.
   *
   * @todo Delete the last two cases in https://www.drupal.org/project/drupal/issues/3521221. The last one is `intval('none')`.
   */
  #[TestWith([NULL])]
  #[TestWith(["none"])]
  #[TestWith([0])]
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
   */
  #[TestWith([5, 5])]
  #[TestWith(["5", 5])]
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

<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin\area;

use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\area\View as ViewAreaPlugin;

/**
 * @coversDefaultClass \Drupal\views\Plugin\views\area\View
 * @group views
 */
class ViewTest extends UnitTestCase {

  /**
   * The mocked entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityStorage;

  /**
   * The view handler.
   *
   * @var \Drupal\views\Plugin\views\area\View
   */
  protected $viewHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityStorage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    $this->viewHandler = new ViewAreaPlugin([], 'view', [], $this->entityStorage);
    $this->viewHandler->view = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies(): void {
    /** @var \Drupal\views\Entity\View $view_this */
    $view_this = $this->createMock('Drupal\views\ViewEntityInterface');
    $view_this->expects($this->any())->method('getConfigDependencyKey')->willReturn('config');
    $view_this->expects($this->any())->method('getConfigDependencyName')->willReturn('view.this');
    $view_this->expects($this->any())->method('id')->willReturn('this');
    $view_other = $this->createMock('Drupal\views\ViewEntityInterface');
    $view_other->expects($this->any())->method('getConfigDependencyKey')->willReturn('config');
    $view_other->expects($this->any())->method('getConfigDependencyName')->willReturn('view.other');
    $this->entityStorage->expects($this->any())
      ->method('load')
      ->willReturnMap([
        ['this', $view_this],
        ['other', $view_other],
      ]);
    $this->viewHandler->view->storage = $view_this;

    $this->viewHandler->options['view_to_insert'] = 'other:default';
    $this->assertEquals(['config' => ['view.other']], $this->viewHandler->calculateDependencies());

    $this->viewHandler->options['view_to_insert'] = 'this:default';
    $this->assertSame([], $this->viewHandler->calculateDependencies());
  }

}

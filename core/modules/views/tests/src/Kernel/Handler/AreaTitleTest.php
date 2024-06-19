<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the title area handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\area\Title
 */
class AreaTitleTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_area_title'];

  /**
   * Tests the title area handler.
   */
  public function testTitleText(): void {
    $view = Views::getView('test_area_title');

    $view->setDisplay('default');
    $this->executeView($view);
    $view->render();
    $this->assertEmpty($view->getTitle(), 'The title area does not override the title if the view is not empty.');
    $view->destroy();

    $view->setDisplay('default');
    $this->executeView($view);
    $view->result = [];
    $view->render();
    $this->assertEquals('test_title_empty', $view->getTitle(), 'The title area should override the title if the result is empty.');
    $view->destroy();

    $view->setDisplay('page_1');
    $this->executeView($view);
    $view->render();
    $this->assertEquals('test_title_header', $view->getTitle(), 'The title area on the header should override the title if the result is not empty.');
    $view->destroy();

    $view->setDisplay('page_1');
    $this->executeView($view);
    $view->result = [];
    $view->render();
    $this->assertEquals('test_title_empty', $view->getTitle(), 'The title area should override the title if the result is empty.');
    $view->destroy();
  }

}

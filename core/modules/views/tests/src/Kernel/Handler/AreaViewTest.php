<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the view area handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\area\View
 */
class AreaViewTest extends ViewsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['user'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_simple_argument', 'test_area_view'];

  /**
   * Tests the view area handler.
   */
  public function testViewArea(): void {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $view = Views::getView('test_area_view');

    // Tests \Drupal\views\Plugin\views\area\View::calculateDependencies().
    $this->assertSame(['config' => ['views.view.test_simple_argument'], 'module' => ['views_test_data']], $view->getDependencies());

    $this->executeView($view);
    $output = $view->render();
    $output = (string) $renderer->renderRoot($output);
    $this->assertStringContainsString('js-view-dom-id-' . $view->dom_id, $output, 'The test view is correctly embedded.');
    $view->destroy();

    $view->setArguments([27]);
    $this->executeView($view);
    $output = $view->render();
    $output = (string) $renderer->renderRoot($output);
    $this->assertStringNotContainsString('John', $output, 'The test view is correctly embedded with inherited arguments.');
    $this->assertStringContainsString('George', $output, 'The test view is correctly embedded with inherited arguments.');
    $view->destroy();
  }

}

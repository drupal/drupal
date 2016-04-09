<?php

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
  public static $modules = array('user');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_simple_argument', 'test_area_view');

  /**
   * Tests the view area handler.
   */
  public function testViewArea() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $view = Views::getView('test_area_view');

    // Tests \Drupal\views\Plugin\views\area\View::calculateDependencies().
    $this->assertIdentical(['config' => ['views.view.test_simple_argument']], $view->getDependencies());

    $this->executeView($view);
    $output = $view->render();
    $output = $renderer->renderRoot($output);
    $this->assertTrue(strpos($output, 'js-view-dom-id-' . $view->dom_id) !== FALSE, 'The test view is correctly embedded.');
    $view->destroy();

    $view->setArguments(array(27));
    $this->executeView($view);
    $output = $view->render();
    $output = $renderer->renderRoot($output);
    $this->assertTrue(strpos($output, 'John') === FALSE, 'The test view is correctly embedded with inherited arguments.');
    $this->assertTrue(strpos($output, 'George') !== FALSE, 'The test view is correctly embedded with inherited arguments.');
    $view->destroy();
  }

}

<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Handler\AreaViewTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Views;

/**
 * Tests the view area handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\area\View
 */
class AreaViewTest extends ViewUnitTestBase {

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
    $view = Views::getView('test_area_view');

    // Tests \Drupal\views\Plugin\views\area\View::calculateDependencies().
    $this->assertIdentical(['config' => ['views.view.test_simple_argument']], $view->calculateDependencies());

    $this->executeView($view);
    $output = $view->render();
    $output = drupal_render($output);
    $this->assertTrue(strpos($output, 'view-test-simple-argument') !== FALSE, 'The test view is correctly embedded.');
    $view->destroy();

    $view->setArguments(array(27));
    $this->executeView($view);
    $output = $view->render();
    $output = drupal_render($output);
    $this->assertTrue(strpos($output, 'John') === FALSE, 'The test view is correctly embedded with inherited arguments.');
    $this->assertTrue(strpos($output, 'George') !== FALSE, 'The test view is correctly embedded with inherited arguments.');
    $view->destroy();
  }

}

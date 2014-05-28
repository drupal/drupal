<?php

/**
 * @file
 * Contains \Drupal\views\Tests\ViewRenderTest.
 */

namespace Drupal\views\Tests;

use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Views;

/**
 * Tests general rendering of a view.
 */
class ViewRenderTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view_render');

  public static function getInfo() {
    return array(
      'name' => 'View render tests',
      'description' => 'Tests the general rendering of a view.',
      'group' => 'Views'
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }


  /**
   * Tests render functionality.
   */
  public function testRender() {
    \Drupal::state()->set('views_render.test', 0);

    // Make sure that the rendering just calls the preprocess function once.
    $view = Views::getView('test_view_render');
    $output = $view->preview();
    drupal_render($output);

    $this->assertEqual(\Drupal::state()->get('views_render.test'), 1);
  }

}

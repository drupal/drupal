<?php

/**
 * @file
 * Contains \Drupal\views\Tests\ViewRenderTest.
 */

namespace Drupal\views\Tests;

use Drupal\views\Tests\ViewTestBase;

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
    $GLOBALS['views_render.test'] = 0;
    // Make sure that the rendering just calls the preprocess function once.
    $view = views_get_view('test_view_render');
    $view->preview();

    $this->assertEqual($GLOBALS['views_render.test'], 1);
  }

}

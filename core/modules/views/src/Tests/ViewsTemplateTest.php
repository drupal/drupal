<?php

/**
 * @file
 * Contains \Drupal\views\Tests\ViewsTemplateTest.
 */

namespace Drupal\views\Tests;

use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Views;

/**
 * Tests the template retrieval of views.
 *
 * @group views
 * @see \Drupal\views_test_data\Plugin\views\style\StyleTemplateTest
 */
class ViewsTemplateTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view_display_template');

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  /**
   * Tests render functionality.
   */
  public function testTemplate() {

    // Make sure that the rendering just calls the preprocess function once.
    $view = Views::getView('test_view_display_template');
    $output = $view->preview();

    // Check if we got the rendered output of our template file.
    $this->assertTrue(strpos(drupal_render($output), 'This module defines its own display template.') !== FALSE, 'Display plugin DisplayTemplateTest defines its own template.');

  }

}

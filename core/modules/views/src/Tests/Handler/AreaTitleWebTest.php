<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Handler\AreaTitleWebTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Entity\View;
use Drupal\views\Tests\ViewTestBase;

/**
 * Tests the title area handler with a web test.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\area\Title
 */
class AreaTitleWebTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_area_title'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  /**
   * Tests the title area handler.
   */
  public function testTitleText() {
    // Confirm that the view has the normal title before making the view return
    // no result.
    $this->drupalGet('test-area-title');
    $this->assertTitle('test_title_header | Drupal');

    // Change the view to return no result.
    /** @var \Drupal\views\Entity\View $view */
    $view = View::load('test_area_title');
    $display =& $view->getDisplay('default');
    $display['display_options']['filters']['name'] = [
      'field' => 'name',
      'id' => 'name',
      'table' => 'views_test_data',
      'relationship' => 'none',
      'plugin_id' => 'string',
      // Add a value which does not exist. The dataset is defined in
      // \Drupal\views\Tests\ViewTestData::dataSet().
      'value' => 'Euler',
    ];
    $view->save();

    $this->drupalGet('test-area-title');
    $this->assertTitle('test_title_empty | Drupal');

    // Change the view to return a result instead.
    /** @var \Drupal\views\Entity\View $view */
    $view = View::load('test_area_title');
    $display =& $view->getDisplay('default');
    $display['display_options']['filters']['name'] = [
      'field' => 'name',
      'id' => 'name',
      'table' => 'views_test_data',
      'relationship' => 'none',
      'plugin_id' => 'string',
      // Change to a value which does exist. The dataset is defined in
      // \Drupal\views\Tests\ViewTestData::dataSet().
      'value' => 'Ringo',
    ];
    $view->save();

    $this->drupalGet('test-area-title');
    $this->assertTitle('test_title_header | Drupal');
  }

}

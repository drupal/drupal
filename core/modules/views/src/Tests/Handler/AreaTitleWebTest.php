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
    $this->drupalGet('test-area-title');
    $this->assertTitle('test_title_header | Drupal');

    // Check the view to return no result.
    /** @var \Drupal\views\Entity\View $view */
    $view = View::load('test_area_title');
    $display =& $view->getDisplay('default');
    $display['display_options']['filters']['id'] = [
      'field' => 'id',
      'id' => 'id',
      'table' => 'views_test_data',
      'relationship' => 'none',
      'plugin_id' => 'numeric',
      // Add a value which does not exist.
      'value' => ['value' => '042118160112'],
    ];
    $view->save();

    $this->drupalGet('test-area-title');
    $this->assertTitle('test_title_empty | Drupal');
  }

}

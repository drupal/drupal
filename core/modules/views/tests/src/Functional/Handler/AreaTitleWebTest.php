<?php

namespace Drupal\Tests\views\Functional\Handler;

use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Entity\View;

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
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->enableViewsTestModule();
  }

  /**
   * Tests the title area handler.
   */
  public function testTitleText() {
    // Confirm that the view has the normal title before making the view return
    // no result.
    $this->drupalGet('test-area-title');
    $this->assertSession()->titleEquals('test_title_header | Drupal');

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
    $this->assertSession()->titleEquals('test_title_empty | Drupal');

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
    $this->assertSession()->titleEquals('test_title_header | Drupal');
  }

}

<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the queryOpBoolean() with default operator.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\filter\BooleanOperator
 */
class FilterBooleanOperatorDefaultTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'views_test_data'];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_view'];

  /**
   * {@inheritdoc}
   */
  protected function viewsData() {
    $views_data = parent::viewsData();

    $views_data['views_test_data']['status']['filter']['id'] = 'boolean_default';

    return $views_data;
  }

  /**
   * Tests the queryOpBoolean() with default operator.
   */
  public function testFilterBooleanOperatorDefault() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('filters', [
      'status' => [
        'id' => 'status',
        'field' => 'status',
        'table' => 'views_test_data',
        'value' => 0,
      ],
    ]);
    $this->executeView($view);
    $this->assertCount(2, $view->result);
  }

}

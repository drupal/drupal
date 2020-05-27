<?php

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;
use Drupal\Core\Database\DatabaseExceptionWrapper;

/**
 * Tests the views exception handling.
 *
 * @group views
 */
class ViewsSqlExceptionTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_filter'];

  /**
   * {@inheritdoc}
   */
  protected function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['name']['filter']['id'] = 'test_exception_filter';
    return $data;
  }

  /**
   * Tests for the SQL exception.
   */
  public function testSqlException() {
    $view = Views::getView('test_filter');
    $view->initDisplay();

    // Adding a filter that will result in an invalid query.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'test_filter' => [
        'id' => 'test_exception_filter',
        'table' => 'views_test_data',
        'field' => 'name',
        'operator' => '=',
        'value' => 'John',
        'group' => 0,
      ],
    ]);

    $this->expectException(DatabaseExceptionWrapper::class);
    $this->expectExceptionMessageMatches('/^Exception in Test filters\[test_filter\]:/');

    $this->executeView($view);
  }

}

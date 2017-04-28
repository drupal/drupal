<?php

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Views;
use Drupal\Core\Database\DatabaseExceptionWrapper;

/**
 * Tests the views exception handling.
 *
 * @group views
 */
class ViewsSqlExceptionTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_filter'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->enableViewsTestModule();
  }

  /**
   * {@inheritdoc}
   */
  protected function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['name']['filter']['id'] = 'test_exception_filter';

    return $data;
  }

  /**
   * Test for the SQL exception.
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

    try {
      $this->executeView($view);
      $this->fail('Expected exception not thrown.');
    }
    catch (DatabaseExceptionWrapper $e) {
      $exception_assert_message = "Exception in {$view->storage->label()}[{$view->storage->id()}]";
      $this->assertEqual(strstr($e->getMessage(), ':', TRUE), $exception_assert_message);
    }
  }

}

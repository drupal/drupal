<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the core date argument handlers.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\argument\Date
 */
class ArgumentDateTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_argument_date'];

  /**
   * Stores the column map for this testCase.
   *
   * @var array
   */
  protected $columnMap = [
    'id' => 'id',
  ];

  /**
   * {@inheritdoc}
   */
  public function viewsData() {
    $data = parent::viewsData();

    $date_plugins = [
      'date_fulldate',
      'date_day',
      'date_month',
      'date_week',
      'date_year',
      'date_year_month',
    ];
    foreach ($date_plugins as $plugin_id) {
      $data['views_test_data'][$plugin_id] = $data['views_test_data']['created'];
      $data['views_test_data'][$plugin_id]['real field'] = 'created';
      $data['views_test_data'][$plugin_id]['argument']['id'] = $plugin_id;
    }
    return $data;
  }

  /**
   * Tests the CreatedFullDate handler.
   *
   * @see \Drupal\node\Plugin\views\argument\CreatedFullDate
   */
  public function testCreatedFullDateHandler(): void {
    $view = Views::getView('test_argument_date');
    $view->setDisplay('default');
    $this->executeView($view, ['20000102']);
    $expected = [];
    $expected[] = ['id' => 2];
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('default');
    $this->executeView($view, ['20000101']);
    $expected = [];
    $expected[] = ['id' => 1];
    $expected[] = ['id' => 3];
    $expected[] = ['id' => 4];
    $expected[] = ['id' => 5];
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('default');
    $this->executeView($view, ['20001023']);
    $expected = [];
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();
  }

  /**
   * Tests the Day handler.
   *
   * @see \Drupal\node\Plugin\views\argument\CreatedDay
   */
  public function testDayHandler(): void {
    $view = Views::getView('test_argument_date');
    $view->setDisplay('embed_1');
    $this->executeView($view, ['02']);
    $expected = [];
    $expected[] = ['id' => 2];
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_1');
    $this->executeView($view, ['01']);
    $expected = [];
    $expected[] = ['id' => 1];
    $expected[] = ['id' => 3];
    $expected[] = ['id' => 4];
    $expected[] = ['id' => 5];
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_1');
    $this->executeView($view, ['23']);
    $expected = [];
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
  }

  /**
   * Tests the Month handler.
   *
   * @see \Drupal\node\Plugin\views\argument\CreatedMonth
   */
  public function testMonthHandler(): void {
    $view = Views::getView('test_argument_date');
    $view->setDisplay('embed_2');
    $this->executeView($view, ['01']);
    $expected = [];
    $expected[] = ['id' => 1];
    $expected[] = ['id' => 2];
    $expected[] = ['id' => 3];
    $expected[] = ['id' => 4];
    $expected[] = ['id' => 5];
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_2');
    $this->executeView($view, ['12']);
    $expected = [];
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
  }

  /**
   * Tests the Week handler.
   *
   * @see \Drupal\node\Plugin\views\argument\CreatedWeek
   */
  public function testWeekHandler(): void {
    $this->container->get('database')->update('views_test_data')
      ->fields(['created' => gmmktime(0, 0, 0, 9, 26, 2008)])
      ->condition('id', 1)
      ->execute();

    $this->container->get('database')->update('views_test_data')
      ->fields(['created' => gmmktime(0, 0, 0, 2, 29, 2004)])
      ->condition('id', 2)
      ->execute();

    $this->container->get('database')->update('views_test_data')
      ->fields(['created' => gmmktime(0, 0, 0, 1, 1, 2000)])
      ->condition('id', 3)
      ->execute();

    $this->container->get('database')->update('views_test_data')
      ->fields(['created' => gmmktime(0, 0, 0, 1, 10, 2000)])
      ->condition('id', 4)
      ->execute();

    $this->container->get('database')->update('views_test_data')
      ->fields(['created' => gmmktime(0, 0, 0, 2, 1, 2000)])
      ->condition('id', 5)
      ->execute();

    $view = Views::getView('test_argument_date');
    $view->setDisplay('embed_3');
    // Check the week calculation for a leap year.
    // @see http://wikipedia.org/wiki/ISO_week_date#Calculation
    $this->executeView($view, ['39']);
    $expected = [];
    $expected[] = ['id' => 1];
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_3');
    // Check the week calculation for the 29th of February in a leap year.
    // @see http://wikipedia.org/wiki/ISO_week_date#Calculation
    $this->executeView($view, ['09']);
    $expected = [];
    $expected[] = ['id' => 2];
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_3');
    // The first jan 2000 was still in the last week of the previous year.
    $this->executeView($view, ['52']);
    $expected = [];
    $expected[] = ['id' => 3];
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_3');
    $this->executeView($view, ['02']);
    $expected = [];
    $expected[] = ['id' => 4];
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_3');
    $this->executeView($view, ['05']);
    $expected = [];
    $expected[] = ['id' => 5];
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_3');
    $this->executeView($view, ['23']);
    $expected = [];
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
  }

  /**
   * Tests the Year handler.
   *
   * @see \Drupal\node\Plugin\views\argument\CreatedYear
   */
  public function testYearHandler(): void {
    $this->container->get('database')->update('views_test_data')
      ->fields(['created' => gmmktime(0, 0, 0, 1, 1, 2001)])
      ->condition('id', 3)
      ->execute();

    $this->container->get('database')->update('views_test_data')
      ->fields(['created' => gmmktime(0, 0, 0, 1, 1, 2002)])
      ->condition('id', 4)
      ->execute();

    $this->container->get('database')->update('views_test_data')
      ->fields(['created' => gmmktime(0, 0, 0, 1, 1, 2002)])
      ->condition('id', 5)
      ->execute();

    $view = Views::getView('test_argument_date');
    $view->setDisplay('embed_4');
    $this->executeView($view, ['2000']);
    $expected = [];
    $expected[] = ['id' => 1];
    $expected[] = ['id' => 2];
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_4');
    $this->executeView($view, ['2001']);
    $expected = [];
    $expected[] = ['id' => 3];
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_4');
    $this->executeView($view, ['2002']);
    $expected = [];
    $expected[] = ['id' => 4];
    $expected[] = ['id' => 5];
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_4');
    $this->executeView($view, ['23']);
    $expected = [];
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
  }

  /**
   * Tests the YearMonth handler.
   *
   * @see \Drupal\node\Plugin\views\argument\CreatedYearMonth
   */
  public function testYearMonthHandler(): void {
    $this->container->get('database')->update('views_test_data')
      ->fields(['created' => gmmktime(0, 0, 0, 1, 1, 2001)])
      ->condition('id', 3)
      ->execute();

    $this->container->get('database')->update('views_test_data')
      ->fields(['created' => gmmktime(0, 0, 0, 4, 1, 2001)])
      ->condition('id', 4)
      ->execute();

    $this->container->get('database')->update('views_test_data')
      ->fields(['created' => gmmktime(0, 0, 0, 4, 1, 2001)])
      ->condition('id', 5)
      ->execute();

    $view = Views::getView('test_argument_date');
    $view->setDisplay('embed_5');
    $this->executeView($view, ['200001']);
    $expected = [];
    $expected[] = ['id' => 1];
    $expected[] = ['id' => 2];
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_5');
    $this->executeView($view, ['200101']);
    $expected = [];
    $expected[] = ['id' => 3];
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_5');
    $this->executeView($view, ['200104']);
    $expected = [];
    $expected[] = ['id' => 4];
    $expected[] = ['id' => 5];
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
    $view->destroy();

    $view->setDisplay('embed_5');
    $this->executeView($view, ['201301']);
    $expected = [];
    $this->assertIdenticalResultset($view, $expected, $this->columnMap);
  }

}

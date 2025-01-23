<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the time interval handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\field\TimeInterval
 */
class FieldTimeIntervalTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var string[]
   */
  public static $testViews = ['test_view'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node_test_views',
  ];

  /**
   * Ages dataset.
   */
  protected array $ages = [
    [0, '0 sec', 2],
    [1000, '16 min', 1],
    [1000000, '1 week 4 days 13 hours 46 min', 4],
    [100000000, '3 years 2 months', 5],
    [NULL, '', 3],
  ];

  /**
   * Tests the TimeInterval handler.
   */
  public function testFieldTimeInterval(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();
    $this->executeView($view);
    foreach ($view->result as $delta => $row) {
      [, $formatted_value, $granularity] = $this->ages[$delta];
      $view->field['age']->options['granularity'] = $granularity;
      $this->assertEquals($formatted_value, $view->field['age']->advancedRender($row));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function schemaDefinition() {
    $schema_definition = parent::schemaDefinition();
    $schema_definition['views_test_data']['fields']['age']['not null'] = FALSE;
    return $schema_definition;
  }

  /**
   * {@inheritdoc}
   */
  protected function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['age']['field']['id'] = 'time_interval';
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function dataSet() {
    $data_set = parent::dataSet();
    foreach ($data_set as $delta => $person) {
      $data_set[$delta]['age'] = $this->ages[$delta][0];
    }
    return $data_set;
  }

}

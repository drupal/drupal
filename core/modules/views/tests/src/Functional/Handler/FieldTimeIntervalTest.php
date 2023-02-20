<?php

namespace Drupal\Tests\views\Functional\Handler;

use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Views;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Tests the time interval handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\field\TimeInterval
 */
class FieldTimeIntervalTest extends ViewTestBase {

  use StringTranslationTrait;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Ages dataset.
   *
   * @var array
   */
  protected $ages = [
    [0, '0 sec', 2],
    [1000, '16 min', 1],
    [1000000, '1 week 4 days 13 hours 46 min', 4],
    // DateFormatter::formatInterval will output 2 because there are no weeks.
    [100000000, '3 years 2 months', 5],
    [NULL, '', 3],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->enableViewsTestModule();
  }

  /**
   * Test TimeInterval handler.
   */
  public function testFieldTimeInterval() {
    $view = Views::getView('test_view');
    $view->setDisplay();
    $this->executeView($view);
    foreach ($view->result as $delta => $row) {
      [$value, $formatted_value, $granularity] = $this->ages[$delta];
      $view->field['age']->options['granularity'] = $granularity;
      $this->assertEquals($formatted_value, $view->field['age']->advancedRender($row));
    }
  }

  /**
   * Overrides \Drupal\views\Tests\ViewUnitTestBase::schemaDefinition().
   */
  protected function schemaDefinition() {
    $schema_definition = parent::schemaDefinition();
    $schema_definition['views_test_data']['fields']['age']['not null'] = FALSE;
    return $schema_definition;
  }

  /**
   * Overrides \Drupal\views\Tests\ViewUnitTestBase::viewsData().
   */
  protected function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['age']['field']['id'] = 'time_interval';
    return $data;
  }

  /**
   * Overrides \Drupal\views\Tests\ViewUnitTestBase::dataSet().
   */
  protected function dataSet() {
    $data_set = parent::dataSet();
    foreach ($data_set as $delta => $person) {
      $data_set[$delta]['age'] = $this->ages[$delta][0];
    }
    return $data_set;
  }

}

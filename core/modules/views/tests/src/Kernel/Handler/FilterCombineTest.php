<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the combine filter handler.
 *
 * @group views
 *
 * @coversDefaultClass \Drupal\views\Plugin\views\filter\Combine
 */
class FilterCombineTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view', 'entity_test_fields'];

  /**
   * Map column names.
   *
   * @var array
   */
  protected $columnMap = [
    'views_test_data_name' => 'name',
    'views_test_data_job' => 'job',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('entity_test');
  }

  public function testFilterCombineContains() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $fields = $view->displayHandlers->get('default')->getOption('fields');
    $view->displayHandlers->get('default')->overrideOption('fields', $fields + [
      'job' => [
        'id' => 'job',
        'table' => 'views_test_data',
        'field' => 'job',
        'relationship' => 'none',
      ],
    ]);

    // Change the filtering.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'age' => [
        'id' => 'combine',
        'table' => 'views',
        'field' => 'combine',
        'relationship' => 'none',
        'operator' => 'contains',
        'fields' => [
          'name',
          'job',
        ],
        'value' => 'iNg',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'John',
        'job' => 'Singer',
      ],
      [
        'name' => 'George',
        'job' => 'Singer',
      ],
      [
        'name' => 'Ringo',
        'job' => 'Drummer',
      ],
      [
        'name' => 'Ginger',
        'job' => NULL,
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  /**
   * Tests the Combine field filter with the 'word' operator.
   */
  public function testFilterCombineWord() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $fields = $view->displayHandlers->get('default')->getOption('fields');
    $view->displayHandlers->get('default')->overrideOption('fields', $fields + [
      'job' => [
        'id' => 'job',
        'table' => 'views_test_data',
        'field' => 'job',
        'relationship' => 'none',
      ],
    ]);

    // Change the filtering.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'age' => [
        'id' => 'combine',
        'table' => 'views',
        'field' => 'combine',
        'relationship' => 'none',
        'operator' => 'word',
        'fields' => [
          'name',
          'job',
        ],
        'value' => 'singer ringo',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'John',
        'job' => 'Singer',
      ],
      [
        'name' => 'George',
        'job' => 'Singer',
      ],
      [
        'name' => 'Ringo',
        'job' => 'Drummer',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  /**
   * Tests the Combine field filter with the 'allwords' operator.
   */
  public function testFilterCombineAllWords() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $fields = $view->displayHandlers->get('default')->getOption('fields');
    $view->displayHandlers->get('default')->overrideOption('fields', $fields + [
      'job' => [
        'id' => 'job',
        'table' => 'views_test_data',
        'field' => 'job',
        'relationship' => 'none',
      ],
    ]);

    // Set the filtering to allwords and simulate searching for a phrase.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'age' => [
        'id' => 'combine',
        'table' => 'views',
        'field' => 'combine',
        'relationship' => 'none',
        'operator' => 'allwords',
        'fields' => [
          'name',
          'job',
          'age',
        ],
        'value' => '25 "john   singer"',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'John',
        'job' => 'Singer',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);

    // Confirm that the query with multiple filters used the "CONCAT_WS"
    // operator.
    $this->assertStringContainsString('CONCAT_WS(', $view->query->query());
  }

  /**
   * Tests if the filter can handle removed fields.
   *
   * Tests the combined filter handler when a field overwrite is done
   * and fields set in the combine filter are removed from the display
   * but not from the combined filter settings.
   */
  public function testFilterCombineContainsFieldsOverwritten() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $fields = $view->displayHandlers->get('default')->getOption('fields');
    $view->displayHandlers->get('default')->overrideOption('fields', $fields + [
      'job' => [
        'id' => 'job',
        'table' => 'views_test_data',
        'field' => 'job',
        'relationship' => 'none',
      ],
    ]);

    // Change the filtering.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'age' => [
        'id' => 'combine',
        'table' => 'views',
        'field' => 'combine',
        'relationship' => 'none',
        'operator' => 'contains',
        'fields' => [
          'name',
          'job',
          // Add a dummy field to the combined fields to simulate
          // a removed or deleted field.
          'dummy',
        ],
        'value' => 'ing',
      ],
    ]);

    $this->executeView($view);
    // Make sure this view will not get displayed.
    $this->assertTrue($view->build_info['fail'], "View build has been marked as failed.");
    // Make sure this view does not pass validation with the right error.
    $errors = $view->validate();
    $this->assertEquals(t('Field %field set in %filter is not set in display %display.', ['%field' => 'dummy', '%filter' => 'Global: Combine fields filter', '%display' => 'Default']), reset($errors['default']));
  }

  /**
   * Tests that the combine field filter is not valid on displays that don't use
   * fields.
   */
  public function testNonFieldsRow() {
    $view = Views::getView('entity_test_fields');
    $view->setDisplay();

    // Set the rows to a plugin type that doesn't support fields.
    $view->displayHandlers->get('default')->overrideOption('row', [
      'type' => 'entity:entity_test',
      'options' => [
        'view_mode' => 'teaser',
      ],
    ]);
    // Change the filtering.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'name' => [
        'id' => 'combine',
        'table' => 'views',
        'field' => 'combine',
        'relationship' => 'none',
        'operator' => 'contains',
        'fields' => [
          'name',
        ],
        'value' => 'ing',
      ],
    ]);
    $this->executeView($view);
    $errors = $view->validate();
    // Check that the right error is shown.
    $this->assertEquals(t('%display: %filter can only be used on displays that use fields. Set the style or row format for that display to one using fields to use the combine field filter.', ['%filter' => 'Global: Combine fields filter', '%display' => 'Default']), reset($errors['default']));

    // Confirm that the query with single filter does not use the "CONCAT_WS"
    // operator.
    $this->assertStringNotContainsString('CONCAT_WS(', $view->query->query());
  }

  /**
   * Tests the Combine field filter using the 'equal' operator.
   *
   * @covers::opEqual
   */
  public function testFilterCombineEqual() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $fields = $view->displayHandlers->get('default')->getOption('fields');
    $view->displayHandlers->get('default')->overrideOption('fields', $fields + [
      'job' => [
        'id' => 'job',
        'table' => 'views_test_data',
        'field' => 'job',
        'relationship' => 'none',
      ],
    ]);

    // Change the filtering.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'age' => [
        'id' => 'combine',
        'table' => 'views',
        'field' => 'combine',
        'relationship' => 'none',
        'operator' => '=',
        'fields' => [
          'job',
        ],
        'value' => 'sInger',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'John',
        'job' => 'Singer',
      ],
      [
        'name' => 'George',
        'job' => 'Singer',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  /**
   * Tests the Combine field filter using the 'not equal' operator.
   *
   * @covers::opEqual
   */
  public function testFilterCombineNotEqual(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $fields = $view->displayHandlers->get('default')->getOption('fields');
    $view->displayHandlers->get('default')->overrideOption('fields', $fields + [
      'job' => [
        'id' => 'job',
        'table' => 'views_test_data',
        'field' => 'job',
        'relationship' => 'none',
      ],
    ]);

    // Change the filtering.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'age' => [
        'id' => 'combine',
        'table' => 'views',
        'field' => 'combine',
        'relationship' => 'none',
        'operator' => '!=',
        'fields' => [
          'job',
        ],
        // The 'I' in 'sInger' is capitalized deliberately because we are
        // testing that search filters are case-insensitive.
        'value' => 'sInger',
      ],
    ]);

    $this->executeView($view);
    $result_set = [
      [
        'name' => 'Ringo',
        'job' => 'Drummer',
      ],
      [
        'name' => 'Paul',
        'job' => 'Songwriter',
      ],
      [
        'name' => 'Meredith',
        'job' => 'Speaker',
      ],
    ];
    $this->assertIdenticalResultset($view, $result_set, $this->columnMap);
  }

  /**
   * Tests the Combine field filter using the 'starts' operator.
   */
  public function testFilterCombineStarts() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $fields = $view->displayHandlers->get('default')->getOption('fields');
    $view->displayHandlers->get('default')->overrideOption('fields', $fields + [
      'job' => [
        'id' => 'job',
        'table' => 'views_test_data',
        'field' => 'job',
        'relationship' => 'none',
      ],
    ]);

    // Change the filtering.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'age' => [
        'id' => 'combine',
        'table' => 'views',
        'field' => 'combine',
        'relationship' => 'none',
        'operator' => 'starts',
        'fields' => [
          'job',
        ],
        'value' => 'sIn',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'John',
        'job' => 'Singer',
      ],
      [
        'name' => 'George',
        'job' => 'Singer',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  /**
   * Tests the Combine field filter using the 'not_starts' operator.
   */
  public function testFilterCombineNotStarts() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $fields = $view->displayHandlers->get('default')->getOption('fields');
    $view->displayHandlers->get('default')->overrideOption('fields', $fields + [
      'job' => [
        'id' => 'job',
        'table' => 'views_test_data',
        'field' => 'job',
        'relationship' => 'none',
      ],
    ]);

    // Change the filtering.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'age' => [
        'id' => 'combine',
        'table' => 'views',
        'field' => 'combine',
        'relationship' => 'none',
        'operator' => 'not_starts',
        'fields' => [
          'job',
        ],
        'value' => 'sIn',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'Ringo',
        'job' => 'Drummer',
      ],
      [
        'name' => 'Paul',
        'job' => 'Songwriter',
      ],
      [
        'name' => 'Meredith',
        'job' => 'Speaker',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  /**
   * Tests the Combine field filter using the 'ends' operator.
   */
  public function testFilterCombineEnds() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $fields = $view->displayHandlers->get('default')->getOption('fields');
    $view->displayHandlers->get('default')->overrideOption('fields', $fields + [
      'job' => [
        'id' => 'job',
        'table' => 'views_test_data',
        'field' => 'job',
        'relationship' => 'none',
      ],
    ]);

    // Change the filtering.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'age' => [
        'id' => 'combine',
        'table' => 'views',
        'field' => 'combine',
        'relationship' => 'none',
        'operator' => 'ends',
        'fields' => [
          'job',
        ],
        'value' => 'Ger',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'John',
        'job' => 'Singer',
      ],
      [
        'name' => 'George',
        'job' => 'Singer',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  /**
   * Tests the Combine field filter using the 'not_ends' operator.
   */
  public function testFilterCombineNotEnds() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $fields = $view->displayHandlers->get('default')->getOption('fields');
    $view->displayHandlers->get('default')->overrideOption('fields', $fields + [
      'job' => [
        'id' => 'job',
        'table' => 'views_test_data',
        'field' => 'job',
        'relationship' => 'none',
      ],
    ]);

    // Change the filtering.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'age' => [
        'id' => 'combine',
        'table' => 'views',
        'field' => 'combine',
        'relationship' => 'none',
        'operator' => 'not_ends',
        'fields' => [
          'job',
        ],
        'value' => 'Ger',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'Ringo',
        'job' => 'Drummer',
      ],
      [
        'name' => 'Paul',
        'job' => 'Songwriter',
      ],
      [
        'name' => 'Meredith',
        'job' => 'Speaker',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  /**
   * Tests the Combine field filter using the 'not' operator.
   */
  public function testFilterCombineNot() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $fields = $view->displayHandlers->get('default')->getOption('fields');
    $view->displayHandlers->get('default')->overrideOption('fields', $fields + [
      'job' => [
        'id' => 'job',
        'table' => 'views_test_data',
        'field' => 'job',
        'relationship' => 'none',
      ],
    ]);

    // Change the filtering.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'age' => [
        'id' => 'combine',
        'table' => 'views',
        'field' => 'combine',
        'relationship' => 'none',
        'operator' => 'not',
        'fields' => [
          'job',
        ],
        'value' => 'singer',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'Ringo',
        'job' => 'Drummer',
      ],
      [
        'name' => 'Paul',
        'job' => 'Songwriter',
      ],
      [
        'name' => 'Meredith',
        'job' => 'Speaker',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  /**
   * Tests the combine filter when no realName is used.
   */
  public function testFilterCombineNoRealName() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $fields = $view->displayHandlers->get('default')->getOption('fields');
    $view->displayHandlers->get('default')->overrideOption('fields', $fields + [
      'name_no_id' => [
        'id' => 'name_no_id',
        'table' => 'views_test_data',
        'field' => 'name_fail',
        'relationship' => 'none',
      ],
    ]);

    // Change the filtering.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'age' => [
        'id' => 'combine',
        'table' => 'views',
        'field' => 'combine',
        'relationship' => 'none',
        'operator' => 'contains',
        'fields' => [
          'name_no_id',
          'job',
        ],
        'value' => 'iNg',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'John',
        'job' => 'Singer',
      ],
      [
        'name' => 'George',
        'job' => 'Singer',
      ],
      [
        'name' => 'Ringo',
        'job' => 'Drummer',
      ],
      [
        'name' => 'Ginger',
        'job' => NULL,
      ],
    ];
    $this->assertNotIdenticalResultset($view, $resultset, $this->columnMap);
  }

  /**
   * Additional data to test the NULL issue.
   */
  protected function dataSet() {
    $data_set = parent::dataSet();
    $data_set[] = [
      'name' => 'Ginger',
      'age' => 25,
      'job' => NULL,
      'created' => gmmktime(0, 0, 0, 1, 2, 2000),
      'status' => 1,
    ];
    return $data_set;
  }

  /**
   * Allow {views_test_data}.job to be NULL.
   *
   * @internal
   */
  protected function schemaDefinition() {
    $schema = parent::schemaDefinition();
    unset($schema['views_test_data']['fields']['job']['not null']);
    return $schema;
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Core\Database\Database;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\filter\StringFilter handler.
 *
 * @group views
 * @group #slow
 */
class FilterStringTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * Map column names.
   *
   * @var array
   */
  protected $columnMap = [
    'views_test_data_name' => 'name',
  ];

  public function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['name']['filter']['allow empty'] = TRUE;
    $data['views_test_data']['job']['filter']['allow empty'] = FALSE;
    $data['views_test_data']['description'] = $data['views_test_data']['name'];

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function schemaDefinition() {
    $schema = parent::schemaDefinition();
    $schema['views_test_data']['fields']['description'] = [
      'description' => "A person's description",
      'type' => 'text',
      'not null' => FALSE,
      'size' => 'big',
    ];

    return $schema;
  }

  /**
   * An extended test dataset.
   */
  protected function dataSet() {
    $dataset = parent::dataSet();
    $dataset[0]['description'] = 'John Winston Ono Lennon, MBE (9 October 1940 – 8 December 1980) was an English musician and singer-songwriter who rose to worldwide fame as one of the founding members of The Beatles, one of the most commercially successful and critically acclaimed acts in the history of popular music. Along with fellow Beatle Paul McCartney, he formed one of the most successful songwriting partnerships of the 20th century.';
    $dataset[1]['description'] = 'George Harrison,[1] MBE (25 February 1943 – 29 November 2001)[2] was an English rock guitarist, singer-songwriter, actor and film producer who achieved international fame as lead guitarist of The Beatles.';
    $dataset[2]['description'] = 'Richard Starkey, MBE (born 7 July 1940), better known by his stage name Ringo Starr, is an English musician, singer-songwriter, and actor who gained worldwide fame as the drummer for The Beatles.';
    $dataset[3]['description'] = 'Sir James Paul McCartney, MBE (born 18 June 1942) is an English musician, singer-songwriter and composer. Formerly of The Beatles (1960–1970) and Wings (1971–1981), McCartney is the most commercially successful songwriter in the history of popular music, according to Guinness World Records.[1]';
    $dataset[4]['description'] = NULL;

    return $dataset;
  }

  /**
   * Build and return a Page view of the views_test_data table.
   *
   * @return \Drupal\views\ViewExecutable
   */
  protected function getBasicPageView() {
    $view = Views::getView('test_view');

    // In order to test exposed filters, we have to disable
    // the exposed forms cache.
    \Drupal::service('views.exposed_form_cache')->reset();

    $view->newDisplay('page', 'Page', 'page_1');
    return $view;
  }

  public function testFilterStringEqual(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Change the filtering
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'name' => [
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'operator' => '=',
        'value' => 'Ringo',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'Ringo',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
    $view->destroy();

    // Get the original dataset
    $data_set = $this->dataSet();
    // Adds a new data point in the views_test_data table.
    $query = Database::getConnection()->insert('views_test_data')
      ->fields(array_keys($data_set[0]));
    $query->values([
      'name' => 'Ringo%',
      'age' => 31,
      'job' => 'Drummer',
      'created' => gmmktime(6, 30, 10, 1, 1, 2000),
      'status' => 1,
      'description' => NULL,
    ]);
    $query->execute();

    $view = Views::getView('test_view');
    $view->setDisplay();

    // Change the filtering
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'name' => [
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'operator' => '=',
        'value' => 'Ringo%',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'Ringo%',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterStringGroupedExposedEqual(): void {
    $filters = $this->getGroupedExposedFilters();
    $view = $this->getBasicPageView();

    // Filter: Name, Operator: =, Value: Ringo
    $filters['name']['group_info']['default_group'] = 1;
    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $view->save();

    $this->executeView($view);

    $resultset = [
      [
        'name' => 'Ringo',
      ],
    ];

    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterStringNotEqual(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Change the filtering
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'name' => [
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'operator' => '!=',
        'value' => 'Ringo',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'John',
      ],
      [
        'name' => 'George',
      ],
      [
        'name' => 'Paul',
      ],
      [
        'name' => 'Meredith',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterStringGroupedExposedNotEqual(): void {
    $filters = $this->getGroupedExposedFilters();
    $view = $this->getBasicPageView();

    // Filter: Name, Operator: !=, Value: Ringo
    $filters['name']['group_info']['default_group'] = '2';

    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $view->save();

    $this->executeView($view);

    $resultset = [
      [
        'name' => 'John',
      ],
      [
        'name' => 'George',
      ],
      [
        'name' => 'Paul',
      ],
      [
        'name' => 'Meredith',
      ],
    ];

    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterStringContains(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Change the filtering
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'name' => [
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'operator' => 'contains',
        'value' => 'iNg',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'Ringo',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterStringGroupedExposedContains(): void {
    $filters = $this->getGroupedExposedFilters();
    $view = $this->getBasicPageView();

    // Filter: Name, Operator: contains, Value: ing
    $filters['name']['group_info']['default_group'] = '3';
    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $view->save();

    $this->executeView($view);

    $resultset = [
      [
        'name' => 'Ringo',
      ],
    ];

    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterStringWord(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Change the filtering
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'description' => [
        'id' => 'description',
        'table' => 'views_test_data',
        'field' => 'description',
        'relationship' => 'none',
        'operator' => 'word',
        'value' => 'aCtor',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'George',
      ],
      [
        'name' => 'Ringo',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
    $view->destroy();

    $view = Views::getView('test_view');
    $view->setDisplay();

    // Change the filtering
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'description' => [
        'id' => 'description',
        'table' => 'views_test_data',
        'field' => 'description',
        'relationship' => 'none',
        'operator' => 'allwords',
        'value' => 'Richard Starkey',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'Ringo',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
    $view->destroy();

    $view = Views::getView('test_view');
    $view->setDisplay();

    // Change the filtering to a sting containing only illegal characters.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'description' => [
        'id' => 'description',
        'table' => 'views_test_data',
        'field' => 'description',
        'relationship' => 'none',
        'operator' => 'allwords',
        'value' => ':-)',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'Ringo',
      ],
      [
        'name' => 'John',
      ],
      [
        'name' => 'George',
      ],
      [
        'name' => 'Paul',
      ],
      [
        'name' => 'Meredith',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset);
  }

  public function testFilterStringGroupedExposedWord(): void {
    $filters = $this->getGroupedExposedFilters();
    $view = $this->getBasicPageView();

    // Filter: Name, Operator: contains, Value: ing
    $filters['name']['group_info']['default_group'] = '3';
    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $view->save();

    $this->executeView($view);

    $resultset = [
      [
        'name' => 'Ringo',
      ],
    ];

    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
    $view->destroy();

    $filters = $this->getGroupedExposedFilters();
    $view = $this->getBasicPageView();

    // Filter: Description, Operator: contains, Value: actor
    $filters['description']['group_info']['default_group'] = '1';
    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'George',
      ],
      [
        'name' => 'Ringo',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterStringStarts(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Change the filtering
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'description' => [
        'id' => 'description',
        'table' => 'views_test_data',
        'field' => 'description',
        'relationship' => 'none',
        'operator' => 'starts',
        'value' => 'gEorge',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'George',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterStringGroupedExposedStarts(): void {
    $filters = $this->getGroupedExposedFilters();
    $view = $this->getBasicPageView();

    // Filter: Name, Operator: starts, Value: George
    $filters['description']['group_info']['default_group'] = 2;
    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $view->save();

    $this->executeView($view);

    $resultset = [
      [
        'name' => 'George',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  /**
   * Tests the string filter with negated 'regular_expression' operator.
   */
  public function testFilterStringGroupedNotRegularExpression(): void {
    $filters = $this->getGroupedExposedFilters();
    $view = $this->getBasicPageView();

    // Filter: Name, Operator: not_regular_expression, Value: ^Rin
    $filters['name']['group_info']['default_group'] = 6;
    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $view->save();
    $this->container->get('router.builder')->rebuild();

    $this->executeView($view);

    $resultset = [
      [
        'name' => 'John',
      ],
      [
        'name' => 'George',
      ],
      [
        'name' => 'Paul',
      ],
      [
        'name' => 'Meredith',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterStringNotStarts(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Change the filtering
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'description' => [
        'id' => 'description',
        'table' => 'views_test_data',
        'field' => 'description',
        'relationship' => 'none',
        'operator' => 'not_starts',
        'value' => 'gEorge',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'John',
      ],
      [
        'name' => 'Ringo',
      ],
      [
        'name' => 'Paul',
      ],
      // There is no Meredith returned because their description is empty
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterStringGroupedExposedNotStarts(): void {
    $filters = $this->getGroupedExposedFilters();
    $view = $this->getBasicPageView();

    // Filter: Name, Operator: not_starts, Value: George
    $filters['description']['group_info']['default_group'] = 3;
    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $view->save();

    $this->executeView($view);

    $resultset = [
      [
        'name' => 'John',
      ],
      [
        'name' => 'Ringo',
      ],
      [
        'name' => 'Paul',
      ],
      // There is no Meredith returned because their description is empty
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterStringEnds(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Change the filtering
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'description' => [
        'id' => 'description',
        'table' => 'views_test_data',
        'field' => 'description',
        'relationship' => 'none',
        'operator' => 'ends',
        'value' => 'bEatles.',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'George',
      ],
      [
        'name' => 'Ringo',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterStringGroupedExposedEnds(): void {
    $filters = $this->getGroupedExposedFilters();
    $view = $this->getBasicPageView();

    // Filter: Description, Operator: ends, Value: Beatles
    $filters['description']['group_info']['default_group'] = 4;
    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $view->save();

    $this->executeView($view);

    $resultset = [
      [
        'name' => 'George',
      ],
      [
        'name' => 'Ringo',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterStringNotEnds(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Change the filtering
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'description' => [
        'id' => 'description',
        'table' => 'views_test_data',
        'field' => 'description',
        'relationship' => 'none',
        'operator' => 'not_ends',
        'value' => 'bEatles.',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'John',
      ],
      [
        'name' => 'Paul',
      ],
      // There is no Meredith returned because their description is empty
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterStringGroupedExposedNotEnds(): void {
    $filters = $this->getGroupedExposedFilters();
    $view = $this->getBasicPageView();

    // Filter: Description, Operator: not_ends, Value: Beatles
    $filters['description']['group_info']['default_group'] = 5;
    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $view->save();

    $this->executeView($view);

    $resultset = [
      [
        'name' => 'John',
      ],
      [
        'name' => 'Paul',
      ],
      // There is no Meredith returned because their description is empty
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterStringNot(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Change the filtering
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'description' => [
        'id' => 'description',
        'table' => 'views_test_data',
        'field' => 'description',
        'relationship' => 'none',
        'operator' => 'not',
        'value' => 'bEatles.',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'John',
      ],
      [
        'name' => 'Paul',
      ],
      // There is no Meredith returned because their description is empty
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterStringGroupedExposedNot(): void {
    $filters = $this->getGroupedExposedFilters();
    $view = $this->getBasicPageView();

    // Filter: Description, Operator: not (does not contains), Value: Beatles
    $filters['description']['group_info']['default_group'] = 6;
    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $view->save();

    $this->executeView($view);

    $resultset = [
      [
        'name' => 'John',
      ],
      [
        'name' => 'Paul',
      ],
      // There is no Meredith returned because their description is empty
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);

  }

  public function testFilterStringShorter(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Change the filtering
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'name' => [
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'operator' => 'shorterthan',
        'value' => 5,
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'John',
      ],
      [
        'name' => 'Paul',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterStringGroupedExposedShorter(): void {
    $filters = $this->getGroupedExposedFilters();
    $view = $this->getBasicPageView();

    // Filter: Name, Operator: shorterthan, Value: 5
    $filters['name']['group_info']['default_group'] = 4;
    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $view->save();

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'John',
      ],
      [
        'name' => 'Paul',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterStringLonger(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Change the filtering
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'name' => [
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'operator' => 'longerthan',
        'value' => 7,
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'Meredith',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterStringGroupedExposedLonger(): void {
    $filters = $this->getGroupedExposedFilters();
    $view = $this->getBasicPageView();

    // Filter: Name, Operator: longerthan, Value: 4
    $filters['name']['group_info']['default_group'] = 5;
    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $view->save();

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'Meredith',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterStringEmpty(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Change the filtering
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'description' => [
        'id' => 'description',
        'table' => 'views_test_data',
        'field' => 'description',
        'relationship' => 'none',
        'operator' => 'empty',
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'Meredith',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  public function testFilterStringGroupedExposedEmpty(): void {
    $filters = $this->getGroupedExposedFilters();
    $view = $this->getBasicPageView();

    // Filter: Description, Operator: empty, Value:
    $filters['description']['group_info']['default_group'] = 7;
    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $view->save();

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'Meredith',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  /**
   * Tests the string filter handler with the negated 'regular_expression' operator.
   */
  public function testFilterStringNotRegularExpression(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Filtering by regular expression pattern.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'age' => [
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'operator' => 'not_regular_expression',
        'value' => [
          'value' => '^Rin',
        ],
      ],
    ]);

    $this->executeView($view);
    $resultset = [
      [
        'name' => 'John',
        'age' => 25,
      ],
      [
        'name' => 'George',
        'age' => 27,
      ],
      [
        'name' => 'Paul',
        'age' => 26,
      ],
      [
        'name' => 'Meredith',
        'age' => 30,
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset, $this->columnMap);
  }

  protected function getGroupedExposedFilters() {
    $filters = [
      'name' => [
        'id' => 'name',
        'plugin_id' => 'string',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'exposed' => TRUE,
        'expose' => [
          'operator' => 'name_op',
          'label' => 'name',
          'identifier' => 'name',
        ],
        'is_grouped' => TRUE,
        'group_info' => [
          'label' => 'name',
          'identifier' => 'name',
          'default_group' => 'All',
          'group_items' => [
            1 => [
              'title' => 'Is Ringo',
              'operator' => '=',
              'value' => 'Ringo',
            ],
            2 => [
              'title' => 'Is not Ringo',
              'operator' => '!=',
              'value' => 'Ringo',
            ],
            3 => [
              'title' => 'Contains ing',
              'operator' => 'contains',
              'value' => 'ing',
            ],
            4 => [
              'title' => 'Shorter than 5 letters',
              'operator' => 'shorterthan',
              'value' => 5,
            ],
            5 => [
              'title' => 'Longer than 7 letters',
              'operator' => 'longerthan',
              'value' => 7,
            ],
            6 => [
              'title' => 'Does not start with Rin',
              'operator' => 'not_regular_expression',
              'value' => '^Rin',
            ],
          ],
        ],
      ],
      'description' => [
        'id' => 'description',
        'plugin_id' => 'string',
        'table' => 'views_test_data',
        'field' => 'description',
        'relationship' => 'none',
        'exposed' => TRUE,
        'expose' => [
          'operator' => 'description_op',
          'label' => 'description',
          'identifier' => 'description',
        ],
        'is_grouped' => TRUE,
        'group_info' => [
          'label' => 'description',
          'identifier' => 'description',
          'default_group' => 'All',
          'group_items' => [
            1 => [
              'title' => 'Contains the word: Actor',
              'operator' => 'word',
              'value' => 'actor',
            ],
            2 => [
              'title' => 'Starts with George',
              'operator' => 'starts',
              'value' => 'George',
            ],
            3 => [
              'title' => 'Not Starts with: George',
              'operator' => 'not_starts',
              'value' => 'George',
            ],
            4 => [
              'title' => 'Ends with: Beatles',
              'operator' => 'ends',
              'value' => 'Beatles.',
            ],
            5 => [
              'title' => 'Not Ends with: Beatles',
              'operator' => 'not_ends',
              'value' => 'Beatles.',
            ],
            6 => [
              'title' => 'Does not contain: Beatles',
              'operator' => 'not',
              'value' => 'Beatles.',
            ],
            7 => [
              'title' => 'Empty description',
              'operator' => 'empty',
              'value' => '',
            ],
          ],
        ],
      ],
    ];
    return $filters;
  }

}

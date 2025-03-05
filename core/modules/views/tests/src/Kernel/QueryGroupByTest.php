<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel;

use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\views\Views;

/**
 * Tests aggregate functionality of views, for example count.
 *
 * @group views
 */
class QueryGroupByTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = [
    'test_group_by_in_filters',
    'test_aggregate_count',
    'test_aggregate_count_function',
    'test_group_by_count',
    'test_group_by_count_multicardinality',
    'test_group_by_field_not_within_bundle',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'system',
    'field',
    'user',
    'language',
  ];

  /**
   * The storage for the test entity type.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  public $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_mul');

    $this->storage = $this->container->get('entity_type.manager')->getStorage('entity_test');

    ConfigurableLanguage::createFromLangcode('it')->save();
  }

  /**
   * Tests aggregate count feature.
   */
  public function testAggregateCount(): void {
    $this->setupTestEntities();

    $view = Views::getView('test_aggregate_count');
    $this->executeView($view);

    $this->assertCount(2, $view->result, 'Make sure the count of items is right.');

    $types = [];
    foreach ($view->result as $item) {
      // num_records is an alias for id.
      $types[$item->entity_test_name] = $item->num_records;
    }

    $this->assertEquals(4, $types['name1']);
    $this->assertEquals(3, $types['name2']);
  }

  /**
   * Tests aggregate count feature with no group by.
   */
  public function testAggregateCountFunction(): void {
    $this->setupTestEntities();

    $view = Views::getView('test_aggregate_count_function');
    $this->executeView($view);

    $this->assertEquals(7, $view->result[0]->id);
    $this->assertCount(1, $view->result, 'Make sure the count of rows is one.');
  }

  /**
   * Provides a test helper which runs a view with some aggregation function.
   *
   * @param string|null $aggregation_function
   *   Which aggregation function should be used, for example sum or count. If
   *   NULL is passed the aggregation will be tested with no function.
   * @param array $values
   *   The expected views result.
   */
  public function groupByTestHelper($aggregation_function, $values): void {
    $this->setupTestEntities();

    $view = Views::getView('test_group_by_count');
    $view->setDisplay();
    // There is no need for a function in order to have aggregation.
    if (empty($aggregation_function)) {
      // The test table has 2 fields ('id' and 'name'). We'll remove 'id'
      // because it's unique and will test aggregation on 'name'.
      unset($view->displayHandlers->get('default')->options['fields']['id']);
    }
    else {
      $view->displayHandlers->get('default')->options['fields']['id']['group_type'] = $aggregation_function;
    }

    $this->executeView($view);

    $this->assertCount(2, $view->result, 'Make sure the count of items is right.');
    // Group by name to identify the right count.
    $results = [];
    foreach ($view->result as $item) {
      $results[$item->entity_test_name] = $item->id;
    }
    $aggregation_function ??= 'NULL';
    $this->assertEquals($values[0], $results['name1'], "Aggregation with $aggregation_function and group by name: name1 returned the expected amount of results");
    $this->assertEquals($values[1], $results['name2'], "Aggregation with $aggregation_function and group by name: name2 returned the expected amount of results");
  }

  /**
   * Helper method that creates some test entities.
   */
  protected function setupTestEntities(): void {
    // Create 4 entities with name1 and 3 entities with name2.
    $entity_1 = [
      'name' => 'name1',
    ];

    $this->storage->create($entity_1)->save();
    $this->storage->create($entity_1)->save();
    $this->storage->create($entity_1)->save();
    $this->storage->create($entity_1)->save();

    $entity_2 = [
      'name' => 'name2',
    ];
    $this->storage->create($entity_2)->save();
    $this->storage->create($entity_2)->save();
    $this->storage->create($entity_2)->save();
  }

  /**
   * Tests the count aggregation function.
   */
  public function testGroupByCount(): void {
    $this->groupByTestHelper('count', [4, 3]);
  }

  /**
   * Tests the sum aggregation function.
   */
  public function testGroupBySum(): void {
    $this->groupByTestHelper('sum', [10, 18]);
  }

  /**
   * Tests the average aggregation function.
   */
  public function testGroupByAverage(): void {
    $this->groupByTestHelper('avg', [2.5, 6]);
  }

  /**
   * Tests the min aggregation function.
   */
  public function testGroupByMin(): void {
    $this->groupByTestHelper('min', [1, 5]);
  }

  /**
   * Tests the max aggregation function.
   */
  public function testGroupByMax(): void {
    $this->groupByTestHelper('max', [4, 7]);
  }

  /**
   * Tests aggregation with no specific function.
   */
  public function testGroupByNone(): void {
    $this->groupByTestHelper(NULL, [1, 5]);
  }

  /**
   * Tests group by with filters.
   */
  public function testGroupByCountOnlyFilters(): void {
    // Check if GROUP BY and HAVING are included when a view
    // doesn't display SUM, COUNT, MAX, etc. functions in SELECT statement.

    for ($x = 0; $x < 10; $x++) {
      $this->storage->create(['name' => 'name1'])->save();
    }

    $view = Views::getView('test_group_by_in_filters');
    $this->executeView($view);

    $this->assertStringContainsString('GROUP BY', (string) $view->build_info['query'], 'Make sure that GROUP BY is in the query');
    $this->assertStringContainsString('HAVING', (string) $view->build_info['query'], 'Make sure that HAVING is in the query');
  }

  /**
   * Tests grouping on base field.
   */
  public function testGroupByBaseField(): void {
    $this->setupTestEntities();

    $view = Views::getView('test_group_by_count');
    $view->setDisplay();
    // This tests that the GROUP BY portion of the query is properly formatted
    // to include the base table to avoid ambiguous field errors.
    $view->displayHandlers->get('default')->options['fields']['name']['group_type'] = 'min';
    unset($view->displayHandlers->get('default')->options['fields']['id']['group_type']);
    $this->executeView($view);
    $this->assertMatchesRegularExpression('/GROUP BY .*[^\w\s]entity_test[^\w\s]\.[^\w\s]id[^\w\s]/', (string) $view->build_info['query'], 'GROUP BY field includes the base table name when grouping on the base field.');
  }

  /**
   * Tests grouping a field with cardinality > 1.
   */
  public function testGroupByFieldWithCardinality(): void {
    $field_storage = FieldStorageConfig::create([
      'type' => 'integer',
      'field_name' => 'field_test',
      'cardinality' => 4,
      'entity_type' => 'entity_test_mul',
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'entity_test_mul',
      'bundle' => 'entity_test_mul',
    ]);
    $field->save();

    $entities = [];
    $entity = EntityTestMul::create([
      'field_test' => [1, 1, 1],
    ]);
    $entity->save();
    $entities[] = $entity;

    $entity = EntityTestMul::create([
      'field_test' => [2, 2, 2],
    ]);
    $entity->save();
    $entities[] = $entity;

    $entity = EntityTestMul::create([
      'field_test' => [2, 2, 2],
    ]);
    $entity->save();
    $entities[] = $entity;

    $view = Views::getView('test_group_by_count_multicardinality');
    $this->executeView($view);
    $this->assertCount(2, $view->result);

    $this->assertEquals('3', $view->getStyle()->getField(0, 'id'));
    $this->assertEquals('1', $view->getStyle()->getField(0, 'field_test'));
    $this->assertEquals('6', $view->getStyle()->getField(1, 'id'));
    $this->assertEquals('2', $view->getStyle()->getField(1, 'field_test'));

    $entities[2]->field_test[0]->value = 3;
    $entities[2]->field_test[1]->value = 4;
    $entities[2]->field_test[2]->value = 5;
    $entities[2]->save();

    $view = Views::getView('test_group_by_count_multicardinality');
    $this->executeView($view);
    $this->assertCount(5, $view->result);

    $this->assertEquals('3', $view->getStyle()->getField(0, 'id'));
    $this->assertEquals('1', $view->getStyle()->getField(0, 'field_test'));
    $this->assertEquals('3', $view->getStyle()->getField(1, 'id'));
    $this->assertEquals('2', $view->getStyle()->getField(1, 'field_test'));
    $this->assertEquals('1', $view->getStyle()->getField(2, 'id'));
    $this->assertEquals('3', $view->getStyle()->getField(2, 'field_test'));
    $this->assertEquals('1', $view->getStyle()->getField(3, 'id'));
    $this->assertEquals('4', $view->getStyle()->getField(3, 'field_test'));
    $this->assertEquals('1', $view->getStyle()->getField(4, 'id'));
    $this->assertEquals('5', $view->getStyle()->getField(4, 'field_test'));

    // Check that translated values are correctly retrieved and are not grouped
    // into the original entity.
    $translation = $entity->addTranslation('it');
    $translation->field_test = [6, 6, 6];
    $translation->save();

    $view = Views::getView('test_group_by_count_multicardinality');
    $this->executeView($view);

    $this->assertCount(6, $view->result);
    $this->assertEquals('3', $view->getStyle()->getField(5, 'id'));
    $this->assertEquals('6', $view->getStyle()->getField(5, 'field_test'));
  }

  /**
   * Tests group by with a non-existent field on some bundle.
   */
  public function testGroupByWithFieldsNotExistingOnBundle(): void {
    $field_storage = FieldStorageConfig::create([
      'type' => 'integer',
      'field_name' => 'field_test',
      'cardinality' => 4,
      'entity_type' => 'entity_test_mul',
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'entity_test_mul',
      'bundle' => 'entity_test_mul',
    ]);
    $field->save();

    $entities = [];
    $entity = EntityTestMul::create([
      'field_test' => [1],
      'type' => 'entity_test_mul',
    ]);
    $entity->save();
    $entities[] = $entity;

    $entity = EntityTestMul::create([
      'type' => 'entity_test_mul2',
    ]);
    $entity->save();
    $entities[] = $entity;

    $view = Views::getView('test_group_by_field_not_within_bundle');
    $this->executeView($view);

    $this->assertCount(2, $view->result);
    // The first result is coming from entity_test_mul2, so no field could be
    // rendered.
    $this->assertEquals('', $view->getStyle()->getField(0, 'field_test'));
    // The second result is coming from entity_test_mul, so its field value
    // could be rendered.
    $this->assertEquals('1', $view->getStyle()->getField(1, 'field_test'));
  }

}

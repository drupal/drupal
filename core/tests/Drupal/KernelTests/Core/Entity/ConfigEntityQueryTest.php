<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Config\Entity\Query\QueryFactory;
use Drupal\config_test\Entity\ConfigQueryTest;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Config Entity Query functionality.
 *
 * @group Entity
 * @see \Drupal\Core\Config\Entity\Query
 */
class ConfigEntityQueryTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['config_test'];

  /**
   * Stores the search results for alter comparison.
   *
   * @var array
   */
  protected $queryResults;

  /**
   * The query factory used to construct all queries in the test.
   *
   * @var \Drupal\Core\Config\Entity\Query\QueryFactory
   */
  protected $factory;

  /**
   * The config entity storage used for testing.
   */
  protected ConfigEntityStorageInterface $entityStorage;

  /**
   * Stores all config entities created for the test.
   *
   * @var array
   */
  protected $entities;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entities = [];
    $this->entityStorage = $this->container->get('entity_type.manager')->getStorage('config_query_test');

    // These two are here to make sure that matchArray needs to go over several
    // non-matches on every levels.
    $array['level1']['level2a'] = 9;
    $array['level1a']['level2'] = 9;
    // The tests match array.level1.level2.
    $array['level1']['level2'] = 1;
    $entity = ConfigQueryTest::create([
      'label' => 'entity_1',
      'id' => '1',
      'number' => 31,
      'array' => $array,
    ]);
    $this->entities[] = $entity;
    $entity->enforceIsNew();
    $entity->save();

    $array['level1']['level2'] = 2;
    $entity = ConfigQueryTest::create([
      'label' => 'entity_2',
      'id' => '2',
      'number' => 41,
      'array' => $array,
    ]);
    $this->entities[] = $entity;
    $entity->enforceIsNew();
    $entity->save();

    $array['level1']['level2'] = 1;
    $entity = ConfigQueryTest::create([
      'label' => 'test_prefix_entity_3',
      'id' => '3',
      'number' => 59,
      'array' => $array,
    ]);
    $this->entities[] = $entity;
    $entity->enforceIsNew();
    $entity->save();

    $array['level1']['level2'] = 2;
    $entity = ConfigQueryTest::create([
      'label' => 'entity_4_test_suffix',
      'id' => '4',
      'number' => 26,
      'array' => $array,
    ]);
    $this->entities[] = $entity;
    $entity->enforceIsNew();
    $entity->save();

    $array['level1']['level2'] = 3;
    $entity = ConfigQueryTest::create([
      'label' => 'entity_5_TEST_contains_entity_5',
      'id' => '5',
      'number' => 53,
      'array' => $array,
    ]);
    $this->entities[] = $entity;
    $entity->enforceIsNew();
    $entity->save();

    $array['level1'] = [];
    $entity = ConfigQueryTest::create([
      'label' => $this->randomMachineName(),
      'id' => '6',
      'array' => $array,
    ]);
    $this->entities[] = $entity;
    $entity->enforceIsNew();
    $entity->save();

    $array['level1']['level2'] = 4;
    $entity = ConfigQueryTest::create([
      'label' => $this->randomMachineName(),
      'id' => '7',
      'number' => 70,
      'array' => $array,
    ]);
    $this->entities[] = $entity;
    $entity->enforceIsNew();
    $entity->save();
  }

  /**
   * Tests basic functionality.
   */
  public function testConfigEntityQuery() {
    // Run a test without any condition.
    $this->queryResults = $this->entityStorage->getQuery()
      ->execute();
    $this->assertResults(['1', '2', '3', '4', '5', '6', '7']);
    // No conditions, OR.
    $this->queryResults = $this->entityStorage->getQuery('OR')
      ->execute();
    $this->assertResults(['1', '2', '3', '4', '5', '6', '7']);

    // Filter by ID with equality.
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('id', '3')
      ->execute();
    $this->assertResults(['3']);

    // Filter by label with a known prefix.
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('label', 'test_prefix', 'STARTS_WITH')
      ->execute();
    $this->assertResults(['3']);

    // Filter by label with a known suffix.
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('label', 'test_suffix', 'ENDS_WITH')
      ->execute();
    $this->assertResults(['4']);

    // Filter by label with a known containing word.
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('label', 'test_contains', 'CONTAINS')
      ->execute();
    $this->assertResults(['5']);

    // Filter by ID with the IN operator.
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('id', ['2', '3'], 'IN')
      ->execute();
    $this->assertResults(['2', '3']);

    // Filter by ID with the implicit IN operator.
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('id', ['2', '3'])
      ->execute();
    $this->assertResults(['2', '3']);

    // Filter by ID with the > operator.
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('id', '3', '>')
      ->execute();
    $this->assertResults(['4', '5', '6', '7']);

    // Filter by ID with the >= operator.
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('id', '3', '>=')
      ->execute();
    $this->assertResults(['3', '4', '5', '6', '7']);

    // Filter by ID with the <> operator.
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('id', '3', '<>')
      ->execute();
    $this->assertResults(['1', '2', '4', '5', '6', '7']);

    // Filter by ID with the < operator.
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('id', '3', '<')
      ->execute();
    $this->assertResults(['1', '2']);

    // Filter by ID with the <= operator.
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('id', '3', '<=')
      ->execute();
    $this->assertResults(['1', '2', '3']);

    // Filter by two conditions on the same field.
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('label', 'test_pref', 'STARTS_WITH')
      ->condition('label', 'test_prefix', 'STARTS_WITH')
      ->execute();
    $this->assertResults(['3']);

    // Filter by two conditions on different fields. The first query matches for
    // a different ID, so the result is empty.
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('label', 'test_prefix', 'STARTS_WITH')
      ->condition('id', '5')
      ->execute();
    $this->assertResults([]);

    // Filter by two different conditions on different fields. This time the
    // first condition matches on one item, but the second one does as well.
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('label', 'test_prefix', 'STARTS_WITH')
      ->condition('id', '3')
      ->execute();
    $this->assertResults(['3']);

    // Filter by two different conditions, of which the first one matches for
    // every entry, the second one as well, but just the third one filters so
    // that just two are left.
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('id', '1', '>=')
      ->condition('number', 10, '>=')
      ->condition('number', 50, '>=')
      ->execute();
    $this->assertResults(['3', '5', '7']);

    // Filter with an OR condition group.
    $this->queryResults = $this->entityStorage->getQuery('OR')
      ->condition('id', 1)
      ->condition('id', '2')
      ->execute();
    $this->assertResults(['1', '2']);

    // Simplify it with IN.
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('id', ['1', '2'])
      ->execute();
    $this->assertResults(['1', '2']);
    // Try explicit IN.
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('id', ['1', '2'], 'IN')
      ->execute();
    $this->assertResults(['1', '2']);
    // Try not IN.
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('id', ['1', '2'], 'NOT IN')
      ->execute();
    $this->assertResults(['3', '4', '5', '6', '7']);

    // Filter with an OR condition group on different fields.
    $this->queryResults = $this->entityStorage->getQuery('OR')
      ->condition('id', 1)
      ->condition('number', 41)
      ->execute();
    $this->assertResults(['1', '2']);

    // Filter with an OR condition group on different fields but matching on the
    // same entity.
    $this->queryResults = $this->entityStorage->getQuery('OR')
      ->condition('id', 1)
      ->condition('number', 31)
      ->execute();
    $this->assertResults(['1']);

    // NO simple conditions, YES complex conditions, 'AND'.
    $query = $this->entityStorage->getQuery('AND');
    $and_condition_1 = $query->orConditionGroup()
      ->condition('id', '2')
      ->condition('label', $this->entities[0]->label);
    $and_condition_2 = $query->orConditionGroup()
      ->condition('id', 1)
      ->condition('label', $this->entities[3]->label);
    $this->queryResults = $query
      ->condition($and_condition_1)
      ->condition($and_condition_2)
      ->execute();
    $this->assertResults(['1']);

    // NO simple conditions, YES complex conditions, 'OR'.
    $query = $this->entityStorage->getQuery('OR');
    $and_condition_1 = $query->andConditionGroup()
      ->condition('id', 1)
      ->condition('label', $this->entities[0]->label);
    $and_condition_2 = $query->andConditionGroup()
      ->condition('id', '2')
      ->condition('label', $this->entities[1]->label);
    $this->queryResults = $query
      ->condition($and_condition_1)
      ->condition($and_condition_2)
      ->execute();
    $this->assertResults(['1', '2']);

    // YES simple conditions, YES complex conditions, 'AND'.
    $query = $this->entityStorage->getQuery('AND');
    $and_condition_1 = $query->orConditionGroup()
      ->condition('id', '2')
      ->condition('label', $this->entities[0]->label);
    $and_condition_2 = $query->orConditionGroup()
      ->condition('id', 1)
      ->condition('label', $this->entities[3]->label);
    $this->queryResults = $query
      ->condition('number', 31)
      ->condition($and_condition_1)
      ->condition($and_condition_2)
      ->execute();
    $this->assertResults(['1']);

    // YES simple conditions, YES complex conditions, 'OR'.
    $query = $this->entityStorage->getQuery('OR');
    $and_condition_1 = $query->orConditionGroup()
      ->condition('id', '2')
      ->condition('label', $this->entities[0]->label);
    $and_condition_2 = $query->orConditionGroup()
      ->condition('id', 1)
      ->condition('label', $this->entities[3]->label);
    $this->queryResults = $query
      ->condition('number', 53)
      ->condition($and_condition_1)
      ->condition($and_condition_2)
      ->execute();
    $this->assertResults(['1', '2', '4', '5']);

    // Test the exists and notExists conditions.
    $this->queryResults = $this->entityStorage->getQuery()
      ->exists('id')
      ->execute();
    $this->assertResults(['1', '2', '3', '4', '5', '6', '7']);

    $this->queryResults = $this->entityStorage->getQuery()
      ->exists('non-existent')
      ->execute();
    $this->assertResults([]);

    $this->queryResults = $this->entityStorage->getQuery()
      ->notExists('id')
      ->execute();
    $this->assertResults([]);

    $this->queryResults = $this->entityStorage->getQuery()
      ->notExists('non-existent')
      ->execute();
    $this->assertResults(['1', '2', '3', '4', '5', '6', '7']);
  }

  /**
   * Tests ID conditions.
   */
  public function testStringIdConditions() {
    // We need an entity with a non-numeric ID.
    $entity = ConfigQueryTest::create([
      'label' => 'entity_1',
      'id' => 'foo.bar',
    ]);
    $this->entities[] = $entity;
    $entity->enforceIsNew();
    $entity->save();

    // Test 'STARTS_WITH' condition.
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('id', 'foo.bar', 'STARTS_WITH')
      ->execute();
    $this->assertResults(['foo.bar']);
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('id', 'f', 'STARTS_WITH')
      ->execute();
    $this->assertResults(['foo.bar']);
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('id', 'miss', 'STARTS_WITH')
      ->execute();
    $this->assertResults([]);

    // Test 'CONTAINS' condition.
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('id', 'foo.bar', 'CONTAINS')
      ->execute();
    $this->assertResults(['foo.bar']);
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('id', 'oo.ba', 'CONTAINS')
      ->execute();
    $this->assertResults(['foo.bar']);
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('id', 'miss', 'CONTAINS')
      ->execute();
    $this->assertResults([]);

    // Test 'ENDS_WITH' condition.
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('id', 'foo.bar', 'ENDS_WITH')
      ->execute();
    $this->assertResults(['foo.bar']);
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('id', 'r', 'ENDS_WITH')
      ->execute();
    $this->assertResults(['foo.bar']);
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('id', 'miss', 'ENDS_WITH')
      ->execute();
    $this->assertResults([]);
  }

  /**
   * Tests count query.
   */
  public function testCount() {
    // Test count on no conditions.
    $count = $this->entityStorage->getQuery()
      ->count()
      ->execute();
    $this->assertCount($count, $this->entities);

    // Test count on a complex query.
    $query = $this->entityStorage->getQuery('OR');
    $and_condition_1 = $query->andConditionGroup()
      ->condition('id', 1)
      ->condition('label', $this->entities[0]->label);
    $and_condition_2 = $query->andConditionGroup()
      ->condition('id', '2')
      ->condition('label', $this->entities[1]->label);
    $count = $query
      ->condition($and_condition_1)
      ->condition($and_condition_2)
      ->count()
      ->execute();
    $this->assertSame(2, $count);
  }

  /**
   * Tests sorting and range on config entity queries.
   */
  public function testSortRange() {
    // Sort by simple ascending/descending.
    $this->queryResults = $this->entityStorage->getQuery()
      ->sort('number', 'DESC')
      ->execute();
    $this->assertSame(['7', '3', '5', '2', '1', '4', '6'], array_values($this->queryResults));

    $this->queryResults = $this->entityStorage->getQuery()
      ->sort('number', 'ASC')
      ->execute();
    $this->assertSame(['6', '4', '1', '2', '5', '3', '7'], array_values($this->queryResults));

    // Apply some filters and sort.
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('id', '3', '>')
      ->sort('number', 'DESC')
      ->execute();
    $this->assertSame(['7', '5', '4', '6'], array_values($this->queryResults));

    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('id', '3', '>')
      ->sort('number', 'ASC')
      ->execute();
    $this->assertSame(['6', '4', '5', '7'], array_values($this->queryResults));

    // Apply a pager and sort.
    $this->queryResults = $this->entityStorage->getQuery()
      ->sort('number', 'DESC')
      ->range('2', '2')
      ->execute();
    $this->assertSame(['5', '2'], array_values($this->queryResults));

    $this->queryResults = $this->entityStorage->getQuery()
      ->sort('number', 'ASC')
      ->range('2', '2')
      ->execute();
    $this->assertSame(['1', '2'], array_values($this->queryResults));

    // Add a range to a query without a start parameter.
    $this->queryResults = $this->entityStorage->getQuery()
      ->range(0, '3')
      ->sort('id', 'ASC')
      ->execute();
    $this->assertSame(['1', '2', '3'], array_values($this->queryResults));

    // Apply a pager with limit 4.
    $this->queryResults = $this->entityStorage->getQuery()
      ->pager('4', 0)
      ->sort('id', 'ASC')
      ->execute();
    $this->assertSame(['1', '2', '3', '4'], array_values($this->queryResults));
  }

  /**
   * Tests sorting with tableSort on config entity queries.
   */
  public function testTableSort() {
    $header = [
      ['data' => 'ID', 'specifier' => 'id'],
      ['data' => 'Number', 'specifier' => 'number'],
    ];

    // Sort key: id
    // Sorting with 'DESC' upper case
    $this->queryResults = $this->entityStorage->getQuery()
      ->tableSort($header)
      ->sort('id', 'DESC')
      ->execute();
    $this->assertSame(['7', '6', '5', '4', '3', '2', '1'], array_values($this->queryResults));

    // Sorting with 'ASC' upper case
    $this->queryResults = $this->entityStorage->getQuery()
      ->tableSort($header)
      ->sort('id', 'ASC')
      ->execute();
    $this->assertSame(['1', '2', '3', '4', '5', '6', '7'], array_values($this->queryResults));

    // Sorting with 'desc' lower case
    $this->queryResults = $this->entityStorage->getQuery()
      ->tableSort($header)
      ->sort('id', 'desc')
      ->execute();
    $this->assertSame(['7', '6', '5', '4', '3', '2', '1'], array_values($this->queryResults));

    // Sorting with 'asc' lower case
    $this->queryResults = $this->entityStorage->getQuery()
      ->tableSort($header)
      ->sort('id', 'asc')
      ->execute();
    $this->assertSame(['1', '2', '3', '4', '5', '6', '7'], array_values($this->queryResults));

    // Sort key: number
    // Sorting with 'DeSc' mixed upper and lower case
    $this->queryResults = $this->entityStorage->getQuery()
      ->tableSort($header)
      ->sort('number', 'DeSc')
      ->execute();
    $this->assertSame(['7', '3', '5', '2', '1', '4', '6'], array_values($this->queryResults));

    // Sorting with 'AsC' mixed upper and lower case
    $this->queryResults = $this->entityStorage->getQuery()
      ->tableSort($header)
      ->sort('number', 'AsC')
      ->execute();
    $this->assertSame(['6', '4', '1', '2', '5', '3', '7'], array_values($this->queryResults));

    // Sorting with 'dEsC' mixed upper and lower case
    $this->queryResults = $this->entityStorage->getQuery()
      ->tableSort($header)
      ->sort('number', 'dEsC')
      ->execute();
    $this->assertSame(['7', '3', '5', '2', '1', '4', '6'], array_values($this->queryResults));

    // Sorting with 'aSc' mixed upper and lower case
    $this->queryResults = $this->entityStorage->getQuery()
      ->tableSort($header)
      ->sort('number', 'aSc')
      ->execute();
    $this->assertSame(['6', '4', '1', '2', '5', '3', '7'], array_values($this->queryResults));
  }

  /**
   * Tests dotted path matching.
   */
  public function testDotted() {
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('array.level1.*', 1)
      ->execute();
    $this->assertResults(['1', '3']);
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('*.level1.level2', 2)
      ->execute();
    $this->assertResults(['2', '4']);
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('array.level1.*', 3)
      ->execute();
    $this->assertResults(['5']);
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('array.level1.level2', 3)
      ->execute();
    $this->assertResults(['5']);
    // Test dotted sorting.
    $this->queryResults = $this->entityStorage->getQuery()
      ->sort('array.level1.level2')
      ->execute();
    $this->assertResults(['6', '1', '3', '2', '4', '5', '7']);
    $this->queryResults = $this->entityStorage->getQuery()
      ->sort('array.level1.level2', 'DESC')
      ->execute();
    $this->assertResults(['7', '5', '2', '4', '1', '3', '6']);
    // Make sure that values on the wildcard level do not match if there are
    // sub-keys defined. This must not find anything even if entity 2 has a
    // top-level key number with value 41.
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('*.level1.level2', 41)
      ->execute();
    $this->assertResults([]);
    // Make sure that "IS NULL" and "IS NOT NULL" work correctly with
    // array-valued fields/keys.
    $this->queryResults = $this->entityStorage->getQuery()
      ->exists('array.level1.level2')
      ->execute();
    $this->assertResults(['1', '2', '3', '4', '5', '7']);
    $this->queryResults = $this->entityStorage->getQuery()
      ->exists('array.level1')
      ->execute();
    $this->assertResults(['1', '2', '3', '4', '5', '6', '7']);
    $this->queryResults = $this->entityStorage->getQuery()
      ->exists('array')
      ->execute();
    $this->assertResults(['1', '2', '3', '4', '5', '6', '7']);
    $this->queryResults = $this->entityStorage->getQuery()
      ->notExists('array.level1.level2')
      ->execute();
    $this->assertResults(['6']);
    $this->queryResults = $this->entityStorage->getQuery()
      ->notExists('array.level1')
      ->execute();
    $this->assertResults([]);
    $this->queryResults = $this->entityStorage->getQuery()
      ->notExists('array')
      ->execute();
    $this->assertResults([]);
    // Make sure that "IS NULL" and "IS NOT NULL" work correctly when the dotted
    // path cannot be fully followed.
    $this->queryResults = $this->entityStorage->getQuery()
      ->exists('does.not.exist')
      ->execute();
    $this->assertResults([]);
    $this->queryResults = $this->entityStorage->getQuery()
      ->notExists('does.not.exist')
      ->execute();
    $this->assertResults(['1', '2', '3', '4', '5', '6', '7']);
  }

  /**
   * Tests case sensitivity.
   */
  public function testCaseSensitivity() {
    // Filter by label with a known containing case-sensitive word.
    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('label', 'TEST', 'CONTAINS')
      ->execute();
    $this->assertResults(['3', '4', '5']);

    $this->queryResults = $this->entityStorage->getQuery()
      ->condition('label', 'test', 'CONTAINS')
      ->execute();
    $this->assertResults(['3', '4', '5']);
  }

  /**
   * Tests lookup keys are added to the key value store.
   */
  public function testLookupKeys() {
    \Drupal::service('state')->set('config_test.lookup_keys', TRUE);
    \Drupal::entityTypeManager()->clearCachedDefinitions();
    $key_value = $this->container->get('keyvalue')->get(QueryFactory::CONFIG_LOOKUP_PREFIX . 'config_test');

    $test_entities = [];
    $storage = \Drupal::entityTypeManager()->getStorage('config_test');
    $entity = $storage->create([
      'label' => 'entity_1',
      'id' => '1',
      'style' => 'test',
    ]);
    $test_entities[$entity->getConfigDependencyName()] = $entity;
    $entity->enforceIsNew();
    $entity->save();

    $expected[] = $entity->getConfigDependencyName();
    $this->assertEquals($expected, $key_value->get('style:test'));

    $entity = $storage->create([
      'label' => 'entity_2',
      'id' => '2',
      'style' => 'test',
    ]);
    $test_entities[$entity->getConfigDependencyName()] = $entity;
    $entity->enforceIsNew();
    $entity->save();
    $expected[] = $entity->getConfigDependencyName();
    $this->assertEquals($expected, $key_value->get('style:test'));

    $entity = $storage->create([
      'label' => 'entity_3',
      'id' => '3',
      'style' => 'blah',
    ]);
    $entity->enforceIsNew();
    $entity->save();
    // Do not add this entity to the list of expected result as it has a
    // different value.
    $this->assertEquals($expected, $key_value->get('style:test'));
    $this->assertEquals([$entity->getConfigDependencyName()], $key_value->get('style:blah'));

    // Ensure that a delete clears a key.
    $entity->delete();
    $this->assertNull($key_value->get('style:blah'));

    // Ensure that delete only clears one key.
    $entity_id = array_pop($expected);
    $test_entities[$entity_id]->delete();
    $this->assertEquals($expected, $key_value->get('style:test'));
    $entity_id = array_pop($expected);
    $test_entities[$entity_id]->delete();
    $this->assertNull($key_value->get('style:test'));
  }

  /**
   * Asserts the results as expected regardless of order.
   *
   * @param array $expected
   *   Array of expected entity IDs.
   *
   * @internal
   */
  protected function assertResults(array $expected): void {
    $expected_count = count($expected);
    $this->assertCount($expected_count, $this->queryResults);
    foreach ($expected as $value) {
      // This also tests whether $this->queryResults[$value] is even set at all.
      $this->assertSame($value, $this->queryResults[$value]);
    }
  }

}

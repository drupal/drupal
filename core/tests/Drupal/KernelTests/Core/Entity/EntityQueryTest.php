<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests Entity Query functionality.
 *
 * @group Entity
 */
class EntityQueryTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_test', 'language');

  /**
   * @var array
   */
  protected $queryResults;

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $factory;

  /**
   * A list of bundle machine names created for this test.
   *
   * @var string[]
   */
  protected $bundles;

  /**
   * Field name for the greetings field.
   *
   * @var string
   */
  public $greetings;

  /**
   * Field name for the figures field.
   *
   * @var string
   */
  public $figures;

  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_mulrev');

    $this->installConfig(array('language'));

    $figures = Unicode::strtolower($this->randomMachineName());
    $greetings = Unicode::strtolower($this->randomMachineName());
    foreach (array($figures => 'shape', $greetings => 'text') as $field_name => $field_type) {
      $field_storage = FieldStorageConfig::create(array(
        'field_name' => $field_name,
        'entity_type' => 'entity_test_mulrev',
        'type' => $field_type,
        'cardinality' => 2,
      ));
      $field_storage->save();
      $field_storages[] = $field_storage;
    }
    $bundles = array();
    for ($i = 0; $i < 2; $i++) {
      // For the sake of tablesort, make sure the second bundle is higher than
      // the first one. Beware: MySQL is not case sensitive.
      do {
        $bundle = $this->randomMachineName();
      } while ($bundles && strtolower($bundles[0]) >= strtolower($bundle));
      entity_test_create_bundle($bundle);
      foreach ($field_storages as $field_storage) {
        FieldConfig::create([
          'field_storage' => $field_storage,
          'bundle' => $bundle,
        ])->save();
      }
      $bundles[] = $bundle;
    }
    // Each unit is a list of field name, langcode and a column-value array.
    $units[] = array($figures, 'en', array(
      'color' => 'red',
      'shape' => 'triangle',
    ));
    $units[] = array($figures, 'en', array(
      'color' => 'blue',
      'shape' => 'circle',
    ));
    // To make it easier to test sorting, the greetings get formats according
    // to their langcode.
    $units[] = array($greetings, 'tr', array(
      'value' => 'merhaba',
      'format' => 'format-tr'
    ));
    $units[] = array($greetings, 'pl', array(
      'value' => 'siema',
      'format' => 'format-pl'
    ));
    // Make these languages available to the greetings field.
    ConfigurableLanguage::createFromLangcode('tr')->save();
    ConfigurableLanguage::createFromLangcode('pl')->save();
    // Calculate the cartesian product of the unit array by looking at the
    // bits of $i and add the unit at the bits that are 1. For example,
    // decimal 13 is binary 1101 so unit 3,2 and 0 will be added to the
    // entity.
    for ($i = 1; $i <= 15; $i++) {
      $entity = EntityTestMulRev::create(array(
        'type' => $bundles[$i & 1],
        'name' => $this->randomMachineName(),
        'langcode' => 'en',
      ));
      // Make sure the name is set for every language that we might create.
      foreach (array('tr', 'pl') as $langcode) {
        $entity->addTranslation($langcode)->name = $this->randomMachineName();
      }
      foreach (array_reverse(str_split(decbin($i))) as $key => $bit) {
        if ($bit) {
          list($field_name, $langcode, $values) = $units[$key];
          $entity->getTranslation($langcode)->{$field_name}[] = $values;
        }
      }
      $entity->save();
    }
    $this->bundles = $bundles;
    $this->figures = $figures;
    $this->greetings = $greetings;
    $this->factory = \Drupal::service('entity.query');
  }

  /**
   * Test basic functionality.
   */
  function testEntityQuery() {
    $greetings = $this->greetings;
    $figures = $this->figures;
    $this->queryResults = $this->factory->get('entity_test_mulrev')
      ->exists($greetings, 'tr')
      ->condition("$figures.color", 'red')
      ->sort('id')
      ->execute();
    // As unit 0 was the red triangle and unit 2 was the turkish greeting,
    // bit 0 and bit 2 needs to be set.
    $this->assertResult(5, 7, 13, 15);

    $query = $this->factory->get('entity_test_mulrev', 'OR')
      ->exists($greetings, 'tr')
      ->condition("$figures.color", 'red')
      ->sort('id');
    $count_query = clone $query;
    $this->assertEqual(12, $count_query->count()->execute());
    $this->queryResults = $query->execute();
    // Now bit 0 (1, 3, 5, 7, 9, 11, 13, 15) or bit 2 (4, 5, 6, 7, 12, 13, 14,
    // 15) needs to be set.
    $this->assertResult(1, 3, 4, 5, 6, 7, 9, 11, 12, 13, 14, 15);

    // Test cloning of query conditions.
    $query = $this->factory->get('entity_test_mulrev')
      ->condition("$figures.color", 'red')
      ->sort('id');
    $cloned_query = clone $query;
    $cloned_query
      ->condition("$figures.shape", 'circle');
    // Bit 0 (1, 3, 5, 7, 9, 11, 13, 15) needs to be set.
    $this->queryResults = $query->execute();
    $this->assertResult(1, 3, 5, 7, 9, 11, 13, 15);
    // No red color has a circle shape.
    $this->queryResults = $cloned_query->execute();
    $this->assertResult();

    $query = $this->factory->get('entity_test_mulrev');
    $group = $query->orConditionGroup()
      ->exists($greetings, 'tr')
      ->condition("$figures.color", 'red');
    $this->queryResults = $query
      ->condition($group)
      ->condition("$greetings.value", 'sie', 'STARTS_WITH')
      ->sort('revision_id')
      ->execute();
    // Bit 3 and (bit 0 or 2) -- the above 8 part of the above.
    $this->assertResult(9, 11, 12, 13, 14, 15);

    // No figure has both the colors blue and red at the same time.
    $this->queryResults = $this->factory->get('entity_test_mulrev')
      ->condition("$figures.color", 'blue')
      ->condition("$figures.color", 'red')
      ->sort('id')
      ->execute();
    $this->assertResult();

    // But an entity might have a red and a blue figure both.
    $query = $this->factory->get('entity_test_mulrev');
    $group_blue = $query->andConditionGroup()->condition("$figures.color", 'blue');
    $group_red = $query->andConditionGroup()->condition("$figures.color", 'red');
    $this->queryResults = $query
      ->condition($group_blue)
      ->condition($group_red)
      ->sort('revision_id')
      ->execute();
    // Unit 0 and unit 1, so bits 0 1.
    $this->assertResult(3, 7, 11, 15);

    // Do the same test but with IN operator.
    $query = $this->factory->get('entity_test_mulrev');
    $group_blue = $query->andConditionGroup()->condition("$figures.color", array('blue'), 'IN');
    $group_red = $query->andConditionGroup()->condition("$figures.color", array('red'), 'IN');
    $this->queryResults = $query
      ->condition($group_blue)
      ->condition($group_red)
      ->sort('id')
      ->execute();
    // Unit 0 and unit 1, so bits 0 1.
    $this->assertResult(3, 7, 11, 15);

    // An entity might have either red or blue figure.
    $this->queryResults = $this->factory->get('entity_test_mulrev')
      ->condition("$figures.color", array('blue', 'red'), 'IN')
      ->sort('id')
      ->execute();
    // Bit 0 or 1 is on.
    $this->assertResult(1, 2, 3, 5, 6, 7, 9, 10, 11, 13, 14, 15);

    $this->queryResults = $this->factory->get('entity_test_mulrev')
      ->exists("$figures.color")
      ->notExists("$greetings.value")
      ->sort('id')
      ->execute();
    // Bit 0 or 1 is on but 2 and 3 are not.
    $this->assertResult(1, 2, 3);
    // Now update the 'merhaba' string to xsiemax which is not a meaningful
    // word but allows us to test revisions and string operations.
    $ids = $this->factory->get('entity_test_mulrev')
      ->condition("$greetings.value", 'merhaba')
      ->sort('id')
      ->execute();
    $entities = entity_load_multiple('entity_test_mulrev', $ids);
    $first_entity = reset($entities);
    $old_name = $first_entity->name->value;
    foreach ($entities as $entity) {
      $entity->setNewRevision();
      $entity->getTranslation('tr')->$greetings->value = 'xsiemax';
      $entity->name->value .= 'x';
      $entity->save();
    }
    // We changed the entity names, so the current revision should not match.
    $this->queryResults = $this->factory->get('entity_test_mulrev')
      ->condition('name.value', $old_name)
      ->execute();
    $this->assertResult();
    // Only if all revisions are queried, we find the old revision.
    $this->queryResults = $this->factory->get('entity_test_mulrev')
      ->condition('name.value', $old_name)
      ->allRevisions()
      ->sort('revision_id')
      ->execute();
    $this->assertRevisionResult(array($first_entity->id()), array($first_entity->id()));
    // When querying current revisions, this string is no longer found.
    $this->queryResults = $this->factory->get('entity_test_mulrev')
      ->condition("$greetings.value", 'merhaba')
      ->execute();
    $this->assertResult();
    $this->queryResults = $this->factory->get('entity_test_mulrev')
      ->condition("$greetings.value", 'merhaba')
      ->allRevisions()
      ->sort('revision_id')
      ->execute();
    // The query only matches the original revisions.
    $this->assertRevisionResult(array(4, 5, 6, 7, 12, 13, 14, 15), array(4, 5, 6, 7, 12, 13, 14, 15));
    $results = $this->factory->get('entity_test_mulrev')
      ->condition("$greetings.value", 'siema', 'CONTAINS')
      ->sort('id')
      ->execute();
    // This matches both the original and new current revisions, multiple
    // revisions are returned for some entities.
    $assert = array(16 => '4', 17 => '5', 18 => '6', 19 => '7', 8 => '8', 9 => '9', 10 => '10', 11 => '11', 20 => '12', 21 => '13', 22 => '14', 23 => '15');
    $this->assertIdentical($results, $assert);
    $results = $this->factory->get('entity_test_mulrev')
      ->condition("$greetings.value", 'siema', 'STARTS_WITH')
      ->sort('revision_id')
      ->execute();
    // Now we only get the ones that originally were siema, entity id 8 and
    // above.
    $this->assertIdentical($results, array_slice($assert, 4, 8, TRUE));
    $results = $this->factory->get('entity_test_mulrev')
      ->condition("$greetings.value", 'a', 'ENDS_WITH')
      ->sort('revision_id')
      ->execute();
    // It is very important that we do not get the ones which only have
    // xsiemax despite originally they were merhaba, ie. ended with a.
    $this->assertIdentical($results, array_slice($assert, 4, 8, TRUE));
    $results = $this->factory->get('entity_test_mulrev')
      ->condition("$greetings.value", 'a', 'ENDS_WITH')
      ->allRevisions()
      ->sort('id')
      ->sort('revision_id')
      ->execute();
    // Now we get everything.
    $assert = array(4 => '4', 5 => '5', 6 => '6', 7 => '7', 8 => '8', 9 => '9', 10 => '10', 11 => '11', 12 => '12', 20 => '12', 13 => '13', 21 => '13', 14 => '14', 22 => '14', 15 => '15', 23 => '15');
    $this->assertIdentical($results, $assert);
  }

  /**
   * Test sort().
   *
   * Warning: this is complicated.
   */
  function testSort() {
    $greetings = $this->greetings;
    $figures = $this->figures;
    // Order up and down on a number.
    $this->queryResults = $this->factory->get('entity_test_mulrev')
      ->sort('id')
      ->execute();
    $this->assertResult(range(1, 15));
    $this->queryResults = $this->factory->get('entity_test_mulrev')
      ->sort('id', 'DESC')
      ->execute();
    $this->assertResult(range(15, 1));
    $query = $this->factory->get('entity_test_mulrev')
      ->sort("$figures.color")
      ->sort("$greetings.format")
      ->sort('id');
    // As we do not have any conditions, here are the possible colors and
    // language codes, already in order, with the first occurrence of the
    // entity id marked with *:
    // 8  NULL pl *
    // 12 NULL pl *

    // 4  NULL tr *
    // 12 NULL tr

    // 2  blue NULL *
    // 3  blue NULL *

    // 10 blue pl *
    // 11 blue pl *
    // 14 blue pl *
    // 15 blue pl *

    // 6  blue tr *
    // 7  blue tr *
    // 14 blue tr
    // 15 blue tr

    // 1  red  NULL
    // 3  red  NULL

    // 9  red  pl *
    // 11 red  pl
    // 13 red  pl *
    // 15 red  pl

    // 5  red  tr *
    // 7  red  tr
    // 13 red  tr
    // 15 red  tr
    $count_query = clone $query;
    $this->assertEqual(15, $count_query->count()->execute());
    $this->queryResults = $query->execute();
    $this->assertResult(8, 12, 4, 2, 3, 10, 11, 14, 15, 6, 7, 1, 9, 13, 5);

    // Test the pager by setting element #1 to page 2 with a page size of 4.
    // Results will be #8-12 from above.
    $request = Request::createFromGlobals();
    $request->query->replace(array(
      'page' => '0,2',
    ));
    \Drupal::getContainer()->get('request_stack')->push($request);
    $this->queryResults = $this->factory->get('entity_test_mulrev')
      ->sort("$figures.color")
      ->sort("$greetings.format")
      ->sort('id')
      ->pager(4, 1)
      ->execute();
    $this->assertResult(15, 6, 7, 1);

    // Now test the reversed order.
    $query = $this->factory->get('entity_test_mulrev')
      ->sort("$figures.color", 'DESC')
      ->sort("$greetings.format", 'DESC')
      ->sort('id', 'DESC');
    $count_query = clone $query;
    $this->assertEqual(15, $count_query->count()->execute());
    $this->queryResults = $query->execute();
    $this->assertResult(15, 13, 7, 5, 11, 9, 3, 1, 14, 6, 10, 2, 12, 4, 8);
  }

  /**
   * Test tablesort().
   */
  public function testTableSort() {
    // While ordering on bundles do not give us a definite order, we can still
    // assert that all entities from one bundle are after the other as the
    // order dictates.
    $request = Request::createFromGlobals();
    $request->query->replace(array(
      'sort' => 'asc',
      'order' => 'Type',
    ));
    \Drupal::getContainer()->get('request_stack')->push($request);

    $header = array(
      'id' => array('data' => 'Id', 'specifier' => 'id'),
      'type' => array('data' => 'Type', 'specifier' => 'type'),
    );

    $this->queryResults = array_values($this->factory->get('entity_test_mulrev')
      ->tableSort($header)
      ->execute());
    $this->assertBundleOrder('asc');

    $request->query->add(array(
      'sort' => 'desc',
    ));
    \Drupal::getContainer()->get('request_stack')->push($request);

    $header = array(
      'id' => array('data' => 'Id', 'specifier' => 'id'),
      'type' => array('data' => 'Type', 'specifier' => 'type'),
    );
    $this->queryResults = array_values($this->factory->get('entity_test_mulrev')
      ->tableSort($header)
      ->execute());
    $this->assertBundleOrder('desc');

    // Ordering on ID is definite, however.
    $request->query->add(array(
      'order' => 'Id',
    ));
    \Drupal::getContainer()->get('request_stack')->push($request);
    $this->queryResults = $this->factory->get('entity_test_mulrev')
      ->tableSort($header)
      ->execute();
    $this->assertResult(range(15, 1));
  }

  /**
   * Test that count queries are separated across entity types.
   */
  public function testCount() {
    // Create a field with the same name in a different entity type.
    $field_name = $this->figures;
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'shape',
      'cardinality' => 2,
      'translatable' => TRUE,
    ));
    $field_storage->save();
    $bundle = $this->randomMachineName();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $bundle,
    ])->save();

    $entity = EntityTest::create(array(
      'id' => 1,
      'type' => $bundle,
    ));
    $entity->enforceIsNew();
    $entity->save();

    // As the single entity of this type we just saved does not have a value
    // in the color field, the result should be 0.
    $count = $this->factory->get('entity_test')
      ->exists("$field_name.color")
      ->count()
      ->execute();
     $this->assertFalse($count);
  }

  /**
   * Tests that nested condition groups work as expected.
   */
  public function testNestedConditionGroups() {
    // Query for all entities of the first bundle that have either a red
    // triangle as a figure or the Turkish greeting as a greeting.
    $query = $this->factory->get('entity_test_mulrev');

    $first_and = $query->andConditionGroup()
      ->condition($this->figures . '.color', 'red')
      ->condition($this->figures . '.shape', 'triangle');
    $second_and = $query->andConditionGroup()
      ->condition($this->greetings . '.value', 'merhaba')
      ->condition($this->greetings . '.format', 'format-tr');

    $or = $query->orConditionGroup()
      ->condition($first_and)
      ->condition($second_and);

    $this->queryResults = $query
      ->condition($or)
      ->condition('type', reset($this->bundles))
      ->sort('id')
      ->execute();

    $this->assertResult(6, 14);
  }

  protected function assertResult() {
    $assert = array();
    $expected = func_get_args();
    if ($expected && is_array($expected[0])) {
      $expected = $expected[0];
    }
    foreach ($expected as $binary) {
      $assert[$binary] = strval($binary);
    }
    $this->assertIdentical($this->queryResults, $assert);
  }

  protected function assertRevisionResult($keys, $expected) {
    $assert = array();
    foreach ($expected as $key => $binary) {
      $assert[$keys[$key]] = strval($binary);
    }
    $this->assertIdentical($this->queryResults, $assert);
    return $assert;
  }

  protected function assertBundleOrder($order) {
    // This loop is for bundle1 entities.
    for ($i = 1; $i <= 15; $i += 2) {
      $ok = TRUE;
      $index1 = array_search($i, $this->queryResults);
      $this->assertNotIdentical($index1, FALSE, "$i found at $index1.");
      // This loop is for bundle2 entities.
      for ($j = 2; $j <= 15; $j += 2) {
        if ($ok) {
          if ($order == 'asc') {
            $ok = $index1 > array_search($j, $this->queryResults);
          }
          else {
            $ok = $index1 < array_search($j, $this->queryResults);
          }
        }
      }
      $this->assertTrue($ok, "$i is after all entities in bundle2");
    }
  }

  /**
   * Test adding a tag and metadata to the Entity query object.
   *
   * The tags and metadata should propagate to the SQL query object.
   */
  public function testMetaData() {
    $query = \Drupal::entityQuery('entity_test_mulrev');
    $query
      ->addTag('efq_metadata_test')
      ->addMetaData('foo', 'bar')
      ->execute();

    global $efq_test_metadata;
    $this->assertEqual($efq_test_metadata, 'bar', 'Tag and metadata propagated to the SQL query object.');
  }

  /**
   * Test case sensitive and in-sensitive query conditions.
   */
  public function testCaseSensitivity() {
    $bundle = $this->randomMachineName();

    $field_storage = FieldStorageConfig::create(array(
      'field_name' => 'field_ci',
      'entity_type' => 'entity_test_mulrev',
      'type' => 'string',
      'cardinality' => 1,
      'translatable' => FALSE,
      'settings' => array(
        'case_sensitive' => FALSE,
      )
    ));
    $field_storage->save();

    FieldConfig::create(array(
      'field_storage' => $field_storage,
      'bundle' => $bundle,
    ))->save();

    $field_storage = FieldStorageConfig::create(array(
      'field_name' => 'field_cs',
      'entity_type' => 'entity_test_mulrev',
      'type' => 'string',
      'cardinality' => 1,
      'translatable' => FALSE,
      'settings' => array(
        'case_sensitive' => TRUE,
      ),
    ));
    $field_storage->save();

    FieldConfig::create(array(
      'field_storage' => $field_storage,
      'bundle' => $bundle,
    ))->save();

    $fixtures = array();

    for ($i = 0; $i < 2; $i++) {
      // If the last 4 of the string are all numbers, then there is no
      // difference between upper and lowercase and the case sensitive CONTAINS
      // test will fail. Ensure that can not happen by appending a non-numeric
      // character. See https://www.drupal.org/node/2397297.
      $string = $this->randomMachineName(7) . 'a';
      $fixtures[] = array(
        'original' => $string,
        'uppercase' => Unicode::strtoupper($string),
        'lowercase' => Unicode::strtolower($string),
      );
    }

    EntityTestMulRev::create(array(
      'type' => $bundle,
      'name' => $this->randomMachineName(),
      'langcode' => 'en',
      'field_ci' => $fixtures[0]['uppercase'] . $fixtures[1]['lowercase'],
      'field_cs' => $fixtures[0]['uppercase'] . $fixtures[1]['lowercase']
    ))->save();

    // Check the case insensitive field, = operator.
    $result = \Drupal::entityQuery('entity_test_mulrev')->condition(
      'field_ci', $fixtures[0]['lowercase'] . $fixtures[1]['lowercase']
    )->execute();
    $this->assertIdentical(count($result), 1, 'Case insensitive, lowercase');

    $result = \Drupal::entityQuery('entity_test_mulrev')->condition(
      'field_ci', $fixtures[0]['uppercase'] . $fixtures[1]['uppercase']
    )->execute();
    $this->assertIdentical(count($result), 1, 'Case insensitive, uppercase');

    $result = \Drupal::entityQuery('entity_test_mulrev')->condition(
      'field_ci', $fixtures[0]['uppercase'] . $fixtures[1]['lowercase']
    )->execute();
    $this->assertIdentical(count($result), 1, 'Case insensitive, mixed.');

    // Check the case sensitive field, = operator.
    $result = \Drupal::entityQuery('entity_test_mulrev')->condition(
      'field_cs', $fixtures[0]['lowercase'] . $fixtures[1]['lowercase']
    )->execute();
    $this->assertIdentical(count($result), 0, 'Case sensitive, lowercase.');

    $result = \Drupal::entityQuery('entity_test_mulrev')->condition(
      'field_cs', $fixtures[0]['uppercase'] . $fixtures[1]['uppercase']
    )->execute();
    $this->assertIdentical(count($result), 0, 'Case sensitive, uppercase.');

    $result = \Drupal::entityQuery('entity_test_mulrev')->condition(
      'field_cs', $fixtures[0]['uppercase'] . $fixtures[1]['lowercase']
    )->execute();
    $this->assertIdentical(count($result), 1, 'Case sensitive, exact match.');

    // Check the case insensitive field, IN operator.
    $result = \Drupal::entityQuery('entity_test_mulrev')->condition(
      'field_ci', array($fixtures[0]['lowercase'] . $fixtures[1]['lowercase']), 'IN'
    )->execute();
    $this->assertIdentical(count($result), 1, 'Case insensitive, lowercase');

    $result = \Drupal::entityQuery('entity_test_mulrev')->condition(
      'field_ci', array($fixtures[0]['uppercase'] . $fixtures[1]['uppercase']), 'IN'
    )->execute();
    $this->assertIdentical(count($result), 1, 'Case insensitive, uppercase');

    $result = \Drupal::entityQuery('entity_test_mulrev')->condition(
      'field_ci', array($fixtures[0]['uppercase'] . $fixtures[1]['lowercase']), 'IN'
    )->execute();
    $this->assertIdentical(count($result), 1, 'Case insensitive, mixed');

    // Check the case sensitive field, IN operator.
    $result = \Drupal::entityQuery('entity_test_mulrev')->condition(
      'field_cs', array($fixtures[0]['lowercase'] . $fixtures[1]['lowercase']), 'IN'
    )->execute();
    $this->assertIdentical(count($result), 0, 'Case sensitive, lowercase');

    $result = \Drupal::entityQuery('entity_test_mulrev')->condition(
      'field_cs', array($fixtures[0]['uppercase'] . $fixtures[1]['uppercase']), 'IN'
    )->execute();
    $this->assertIdentical(count($result), 0, 'Case sensitive, uppercase');

    $result = \Drupal::entityQuery('entity_test_mulrev')->condition(
      'field_cs', array($fixtures[0]['uppercase'] . $fixtures[1]['lowercase']), 'IN'
    )->execute();
    $this->assertIdentical(count($result), 1, 'Case sensitive, mixed');

    // Check the case insensitive field, STARTS_WITH operator.
    $result = \Drupal::entityQuery('entity_test_mulrev')->condition(
      'field_ci', $fixtures[0]['lowercase'], 'STARTS_WITH'
    )->execute();
    $this->assertIdentical(count($result), 1, 'Case sensitive, lowercase.');

    $result = \Drupal::entityQuery('entity_test_mulrev')->condition(
      'field_ci', $fixtures[0]['uppercase'], 'STARTS_WITH'
    )->execute();
    $this->assertIdentical(count($result), 1, 'Case sensitive, exact match.');

    // Check the case sensitive field, STARTS_WITH operator.
    $result = \Drupal::entityQuery('entity_test_mulrev')->condition(
      'field_cs', $fixtures[0]['lowercase'], 'STARTS_WITH'
    )->execute();
    $this->assertIdentical(count($result), 0, 'Case sensitive, lowercase.');

    $result = \Drupal::entityQuery('entity_test_mulrev')->condition(
      'field_cs', $fixtures[0]['uppercase'], 'STARTS_WITH'
    )->execute();
    $this->assertIdentical(count($result), 1, 'Case sensitive, exact match.');


    // Check the case insensitive field, ENDS_WITH operator.
    $result = \Drupal::entityQuery('entity_test_mulrev')->condition(
      'field_ci', $fixtures[1]['lowercase'], 'ENDS_WITH'
    )->execute();
    $this->assertIdentical(count($result), 1, 'Case sensitive, lowercase.');

    $result = \Drupal::entityQuery('entity_test_mulrev')->condition(
      'field_ci', $fixtures[1]['uppercase'], 'ENDS_WITH'
    )->execute();
    $this->assertIdentical(count($result), 1, 'Case sensitive, exact match.');

    // Check the case sensitive field, ENDS_WITH operator.
    $result = \Drupal::entityQuery('entity_test_mulrev')->condition(
      'field_cs', $fixtures[1]['lowercase'], 'ENDS_WITH'
    )->execute();
    $this->assertIdentical(count($result), 1, 'Case sensitive, lowercase.');

    $result = \Drupal::entityQuery('entity_test_mulrev')->condition(
      'field_cs', $fixtures[1]['uppercase'], 'ENDS_WITH'
    )->execute();
    $this->assertIdentical(count($result), 0, 'Case sensitive, exact match.');


    // Check the case insensitive field, CONTAINS operator, use the inner 8
    // characters of the uppercase and lowercase strings.
    $result = \Drupal::entityQuery('entity_test_mulrev')->condition(
      'field_ci', Unicode::substr($fixtures[0]['uppercase'] . $fixtures[1]['lowercase'], 4, 8), 'CONTAINS'
    )->execute();
    $this->assertIdentical(count($result), 1, 'Case sensitive, lowercase.');

    $result = \Drupal::entityQuery('entity_test_mulrev')->condition(
      'field_ci', Unicode::strtolower(Unicode::substr($fixtures[0]['uppercase'] . $fixtures[1]['lowercase'], 4, 8)), 'CONTAINS'
    )->execute();
    $this->assertIdentical(count($result), 1, 'Case sensitive, exact match.');

    // Check the case sensitive field, CONTAINS operator.
    $result = \Drupal::entityQuery('entity_test_mulrev')->condition(
      'field_cs', Unicode::substr($fixtures[0]['uppercase'] . $fixtures[1]['lowercase'], 4, 8), 'CONTAINS'
    )->execute();
    $this->assertIdentical(count($result), 1, 'Case sensitive, lowercase.');

    $result = \Drupal::entityQuery('entity_test_mulrev')->condition(
      'field_cs', Unicode::strtolower(Unicode::substr($fixtures[0]['uppercase'] . $fixtures[1]['lowercase'], 4, 8)), 'CONTAINS'
    )->execute();
    $this->assertIdentical(count($result), 0, 'Case sensitive, exact match.');

  }

  /**
   * Test base fields with multiple columns.
   */
  public function testBaseFieldMultipleColumns() {
    $this->enableModules(['taxonomy']);
    $this->installEntitySchema('taxonomy_term');

    Vocabulary::create(['vid' => 'tags']);

    $term1 = Term::create([
      'name' => $this->randomMachineName(),
      'vid' => 'tags',
      'description' => array(
        'value' => $this->randomString(),
        'format' => 'format1',
      )]);
    $term1->save();

    $term2 = Term::create([
      'name' => $this->randomMachineName(),
      'vid' => 'tags',
      'description' => array(
        'value' => $this->randomString(),
        'format' => 'format2',
      )]);
    $term2->save();

    $ids = \Drupal::entityQuery('taxonomy_term')
      ->condition('description.format', 'format1')
      ->execute();

    $this->assertEqual(count($ids), 1);
    $this->assertEqual($term1->id(), reset($ids));
  }

  /**
   * Test forward-revisions.
   */
  public function testForwardRevisions() {
    // Ensure entity 14 is returned.
    $result = \Drupal::entityQuery('entity_test_mulrev')
      ->condition('id', [14], 'IN')
      ->execute();
    $this->assertEqual(count($result), 1);

    // Set a revision on entity 14 that isn't the current default.
    $entity = EntityTestMulRev::load(14);
    $current_values = $entity->{$this->figures}->getValue();

    $entity->setNewRevision(TRUE);
    $entity->isDefaultRevision(FALSE);
    $entity->{$this->figures}->setValue([
      'color' => 'red',
      'shape' => 'square'
    ]);
    $entity->save();

    // Entity query should still return entity 14.
    $result = \Drupal::entityQuery('entity_test_mulrev')
      ->condition('id', [14], 'IN')
      ->execute();
    $this->assertEqual(count($result), 1);

    // Verify that field conditions on the default and forward revision are
    // work as expected.
    $result = \Drupal::entityQuery('entity_test_mulrev')
      ->condition('id', [14], 'IN')
      ->condition("$this->figures.color", $current_values[0]['color'])
      ->execute();
    $this->assertEqual($result, [14 => '14']);
    $result = $this->factory->get('entity_test_mulrev')
      ->condition('id', [14], 'IN')
      ->condition("$this->figures.color", 'red')
      ->allRevisions()
      ->execute();
    $this->assertEqual($result, [16 => '14']);
  }

  /**
   * Test against SQL inject of condition field. This covers a
   * database driver's EntityQuery\Condition class.
   */
  public function testInjectionInCondition() {
    try {
      $this->queryResults = $this->factory->get('entity_test_mulrev')
        ->condition('1 ; -- ', array(0, 1), 'IN')
        ->sort('id')
        ->execute();
      $this->fail('SQL Injection attempt in Entity Query condition in operator should result in an exception.');
    }
    catch (\Exception $e) {
      $this->pass('SQL Injection attempt in Entity Query condition in operator should result in an exception.');
    }
  }
}

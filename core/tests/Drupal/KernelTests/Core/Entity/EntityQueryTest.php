<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Database\Database;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests Entity Query functionality.
 *
 * @group Entity
 */
class EntityQueryTest extends EntityKernelTestBase {

  use EntityReferenceTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['field_test', 'language'];

  /**
   * @var array
   */
  protected $queryResults;

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

  /**
   * The entity_test_mulrev entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test_mulrev');

    $this->installConfig(['language']);

    $figures = mb_strtolower($this->randomMachineName());
    $greetings = mb_strtolower($this->randomMachineName());
    foreach ([$figures => 'shape', $greetings => 'text'] as $field_name => $field_type) {
      $field_storage = FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'entity_test_mulrev',
        'type' => $field_type,
        'cardinality' => 2,
      ]);
      $field_storage->save();
      $field_storages[] = $field_storage;
    }
    $bundles = [];
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
    $units[] = [$figures, 'en',
      [
        'color' => 'red',
        'shape' => 'triangle',
      ],
    ];
    $units[] = [
      $figures,
      'en',
      [
        'color' => 'blue',
        'shape' => 'circle',
      ],
    ];
    // To make it easier to test sorting, the greetings get formats according
    // to their langcode.
    $units[] = [
      $greetings,
      'tr',
      [
        'value' => 'merhaba',
        'format' => 'format-tr',
      ],
    ];
    $units[] = [
      $greetings,
      'pl',
      [
        'value' => 'siema',
        'format' => 'format-pl',
      ],
    ];
    // Make these languages available to the greetings field.
    ConfigurableLanguage::createFromLangcode('tr')->save();
    ConfigurableLanguage::createFromLangcode('pl')->save();
    // Calculate the cartesian product of the unit array by looking at the
    // bits of $i and add the unit at the bits that are 1. For example,
    // decimal 13 is binary 1101 so unit 3,2 and 0 will be added to the
    // entity.
    for ($i = 1; $i <= 15; $i++) {
      $entity = EntityTestMulRev::create([
        'type' => $bundles[$i & 1],
        'name' => $this->randomMachineName(),
        'langcode' => 'en',
      ]);
      // Make sure the name is set for every language that we might create.
      foreach (['tr', 'pl'] as $langcode) {
        $entity->addTranslation($langcode)->name = $this->randomMachineName();
      }
      foreach (array_reverse(str_split(decbin($i))) as $key => $bit) {
        if ($bit) {
          // @todo https://www.drupal.org/project/drupal/issues/3001920 Doing
          //   [$field_name, $langcode, $values] = $units[$key]; causes
          //   problems in PHP 7.3. Revert to better variable names once
          //   https://bugs.php.net/bug.php?id=76937 is fixed.
          $entity->getTranslation($units[$key][1])->{$units[$key][0]}[] = $units[$key][2];
        }
      }
      $entity->save();
    }
    $this->bundles = $bundles;
    $this->figures = $figures;
    $this->greetings = $greetings;
    $this->storage = $this->container->get('entity_type.manager')->getStorage('entity_test_mulrev');
  }

  /**
   * Tests basic functionality.
   */
  public function testEntityQuery() {
    $greetings = $this->greetings;
    $figures = $this->figures;
    $this->queryResults = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->exists($greetings, 'tr')
      ->condition("$figures.color", 'red')
      ->sort('id')
      ->execute();
    // As unit 0 was the red triangle and unit 2 was the turkish greeting,
    // bit 0 and bit 2 needs to be set.
    $this->assertResult(5, 7, 13, 15);

    $query = $this->storage
      ->getQuery('OR')
      ->accessCheck(FALSE)
      ->exists($greetings, 'tr')
      ->condition("$figures.color", 'red')
      ->sort('id');
    $count_query = clone $query;
    $this->assertSame(12, $count_query->count()->execute());
    $this->queryResults = $query->execute();
    // Now bit 0 (1, 3, 5, 7, 9, 11, 13, 15) or bit 2 (4, 5, 6, 7, 12, 13, 14,
    // 15) needs to be set.
    $this->assertResult(1, 3, 4, 5, 6, 7, 9, 11, 12, 13, 14, 15);

    // Test cloning of query conditions.
    $query = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
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

    $query = $this->storage->getQuery()->accessCheck(FALSE);
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
    $this->queryResults = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition("$figures.color", 'blue')
      ->condition("$figures.color", 'red')
      ->sort('id')
      ->execute();
    $this->assertResult();

    // But an entity might have a red and a blue figure both.
    $query = $this->storage->getQuery()->accessCheck(FALSE);
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
    $query = $this->storage->getQuery()->accessCheck(FALSE);
    $group_blue = $query->andConditionGroup()->condition("$figures.color", ['blue'], 'IN');
    $group_red = $query->andConditionGroup()->condition("$figures.color", ['red'], 'IN');
    $this->queryResults = $query
      ->condition($group_blue)
      ->condition($group_red)
      ->sort('id')
      ->execute();
    // Unit 0 and unit 1, so bits 0 1.
    $this->assertResult(3, 7, 11, 15);

    // An entity might have either red or blue figure.
    $this->queryResults = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition("$figures.color", ['blue', 'red'], 'IN')
      ->sort('id')
      ->execute();
    // Bit 0 or 1 is on.
    $this->assertResult(1, 2, 3, 5, 6, 7, 9, 10, 11, 13, 14, 15);

    $this->queryResults = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->exists("$figures.color")
      ->notExists("$greetings.value")
      ->sort('id')
      ->execute();
    // Bit 0 or 1 is on but 2 and 3 are not.
    $this->assertResult(1, 2, 3);
    // Now update the 'merhaba' string to xsiemax which is not a meaningful
    // word but allows us to test revisions and string operations.
    $ids = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition("$greetings.value", 'merhaba')
      ->sort('id')
      ->execute();
    $entities = EntityTestMulRev::loadMultiple($ids);
    $first_entity = reset($entities);
    $old_name = $first_entity->name->value;
    foreach ($entities as $entity) {
      $entity->setNewRevision();
      $entity->getTranslation('tr')->$greetings->value = 'xsiemax';
      $entity->name->value .= 'x';
      $entity->save();
    }
    // Test querying all revisions with a condition on the revision ID field.
    $this->queryResults = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('revision_id', $first_entity->getRevisionId())
      ->allRevisions()
      ->execute();
    $this->assertCount(1, $this->queryResults);
    $this->assertEquals($first_entity->getRevisionId(), key($this->queryResults));
    // We changed the entity names, so the current revision should not match.
    $this->queryResults = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('name.value', $old_name)
      ->execute();
    $this->assertResult();
    // Only if all revisions are queried, we find the old revision.
    $this->queryResults = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('name.value', $old_name)
      ->allRevisions()
      ->sort('revision_id')
      ->execute();
    $this->assertRevisionResult([$first_entity->id()], [$first_entity->id()]);
    // When querying current revisions, this string is no longer found.
    $this->queryResults = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition("$greetings.value", 'merhaba')
      ->execute();
    $this->assertResult();
    $this->queryResults = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition("$greetings.value", 'merhaba')
      ->allRevisions()
      ->sort('revision_id')
      ->execute();
    // The query only matches the original revisions.
    $this->assertRevisionResult([4, 5, 6, 7, 12, 13, 14, 15], [4, 5, 6, 7, 12, 13, 14, 15]);
    $results = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition("$greetings.value", 'siema', 'CONTAINS')
      ->sort('id')
      ->execute();
    // This matches both the original and new current revisions, multiple
    // revisions are returned for some entities.
    $assert = [16 => '4', 17 => '5', 18 => '6', 19 => '7', 8 => '8', 9 => '9', 10 => '10', 11 => '11', 20 => '12', 21 => '13', 22 => '14', 23 => '15'];
    $this->assertSame($assert, $results);
    $results = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition("$greetings.value", 'siema', 'STARTS_WITH')
      ->sort('revision_id')
      ->execute();
    // Now we only get the ones that originally were siema, entity id 8 and
    // above.
    $this->assertSame(array_slice($assert, 4, 8, TRUE), $results);
    $results = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition("$greetings.value", 'a', 'ENDS_WITH')
      ->sort('revision_id')
      ->execute();
    // It is very important that we do not get the ones which only have
    // xsiemax despite originally they were merhaba, ie. ended with a.
    $this->assertSame(array_slice($assert, 4, 8, TRUE), $results);
    $results = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition("$greetings.value", 'a', 'ENDS_WITH')
      ->allRevisions()
      ->sort('id')
      ->sort('revision_id')
      ->execute();
    // Now we get everything.
    $assert = [4 => '4', 5 => '5', 6 => '6', 7 => '7', 8 => '8', 9 => '9', 10 => '10', 11 => '11', 12 => '12', 20 => '12', 13 => '13', 21 => '13', 14 => '14', 22 => '14', 15 => '15', 23 => '15'];
    $this->assertSame($assert, $results);

    // Check that a query on the latest revisions without any condition returns
    // the correct results.
    $results = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->latestRevision()
      ->sort('id')
      ->sort('revision_id')
      ->execute();
    $expected = [1 => '1', 2 => '2', 3 => '3', 16 => '4', 17 => '5', 18 => '6', 19 => '7', 8 => '8', 9 => '9', 10 => '10', 11 => '11', 20 => '12', 21 => '13', 22 => '14', 23 => '15'];
    $this->assertSame($expected, $results);
  }

  /**
   * Tests sort().
   *
   * Warning: this is complicated.
   */
  public function testSort() {
    $greetings = $this->greetings;
    $figures = $this->figures;
    // Order up and down on a number.
    $this->queryResults = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->sort('id')
      ->execute();
    $this->assertResult(range(1, 15));
    $this->queryResults = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->sort('id', 'DESC')
      ->execute();
    $this->assertResult(range(15, 1));
    $query = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
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
    $this->assertSame(15, $count_query->count()->execute());
    $this->queryResults = $query->execute();
    $this->assertResult(8, 12, 4, 2, 3, 10, 11, 14, 15, 6, 7, 1, 9, 13, 5);

    // Test the pager by setting element #1 to page 2 with a page size of 4.
    // Results will be #8-12 from above.
    $request = Request::createFromGlobals();
    $request->query->replace([
      'page' => '0,2',
    ]);
    \Drupal::getContainer()->get('request_stack')->push($request);
    $this->queryResults = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->sort("$figures.color")
      ->sort("$greetings.format")
      ->sort('id')
      ->pager(4, 1)
      ->execute();
    $this->assertResult(15, 6, 7, 1);

    // Now test the reversed order.
    $query = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->sort("$figures.color", 'DESC')
      ->sort("$greetings.format", 'DESC')
      ->sort('id', 'DESC');
    $count_query = clone $query;
    $this->assertSame(15, $count_query->count()->execute());
    $this->queryResults = $query->execute();
    $this->assertResult(15, 13, 7, 5, 11, 9, 3, 1, 14, 6, 10, 2, 12, 4, 8);
  }

  /**
   * Tests tablesort().
   */
  public function testTableSort() {
    // While ordering on bundles do not give us a definite order, we can still
    // assert that all entities from one bundle are after the other as the
    // order dictates.
    $request = Request::createFromGlobals();
    $request->query->replace([
      'sort' => 'asc',
      'order' => 'Type',
    ]);
    \Drupal::getContainer()->get('request_stack')->push($request);

    $header = [
      'id' => ['data' => 'Id', 'specifier' => 'id'],
      'type' => ['data' => 'Type', 'specifier' => 'type'],
    ];

    $this->queryResults = array_values($this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->tableSort($header)
      ->execute());
    $this->assertBundleOrder('asc');

    $request->query->add([
      'sort' => 'desc',
    ]);
    \Drupal::getContainer()->get('request_stack')->push($request);

    $header = [
      'id' => ['data' => 'Id', 'specifier' => 'id'],
      'type' => ['data' => 'Type', 'specifier' => 'type'],
    ];
    $this->queryResults = array_values($this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->tableSort($header)
      ->execute());
    $this->assertBundleOrder('desc');

    // Ordering on ID is definite, however.
    $request->query->add([
      'order' => 'Id',
    ]);
    \Drupal::getContainer()->get('request_stack')->push($request);
    $this->queryResults = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->tableSort($header)
      ->execute();
    $this->assertResult(range(15, 1));
  }

  /**
   * Tests that count queries are separated across entity types.
   */
  public function testCount() {
    // Create a field with the same name in a different entity type.
    $field_name = $this->figures;
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'shape',
      'cardinality' => 2,
      'translatable' => TRUE,
    ]);
    $field_storage->save();
    $bundle = $this->randomMachineName();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $bundle,
    ])->save();

    $entity = EntityTest::create([
      'id' => 1,
      'type' => $bundle,
    ]);
    $entity->enforceIsNew();
    $entity->save();

    // As the single entity of this type we just saved does not have a value
    // in the color field, the result should be 0.
    $count = $this->container->get('entity_type.manager')
      ->getStorage('entity_test')
      ->getQuery()
      ->accessCheck(FALSE)
      ->exists("$field_name.color")
      ->count()
      ->execute();
    $this->assertSame(0, $count);
  }

  /**
   * Tests that nested condition groups work as expected.
   */
  public function testNestedConditionGroups() {
    // Query for all entities of the first bundle that have either a red
    // triangle as a figure or the Turkish greeting as a greeting.
    $query = $this->storage->getQuery()->accessCheck(FALSE);

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

    $this->assertResult(4, 6, 12, 14);
  }

  /**
   * Tests that condition count returns expected number of conditions.
   */
  public function testConditionCount() {
    // Query for all entities of the first bundle that
    // have red as a color AND are triangle shaped.
    $query = $this->storage->getQuery()->accessCheck(FALSE);

    // Add an AND condition group with 2 conditions in it.
    $and_condition_group = $query->andConditionGroup()
      ->condition($this->figures . '.color', 'red')
      ->condition($this->figures . '.shape', 'triangle');

    // We added 2 conditions so count should be 2.
    $this->assertSame(2, $and_condition_group->count());

    // Add an OR condition group with 2 conditions in it.
    $or_condition_group = $query->orConditionGroup()
      ->condition($this->figures . '.color', 'red')
      ->condition($this->figures . '.shape', 'triangle');

    // We added 2 conditions so count should be 2.
    $this->assertSame(2, $or_condition_group->count());
  }

  /**
   * Tests queries with delta conditions.
   */
  public function testDelta() {
    $figures = $this->figures;
    // Test numeric delta value in field condition.
    $this->queryResults = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition("$figures.0.color", 'red')
      ->sort('id')
      ->execute();
    // As unit 0 at delta 0 was the red triangle bit 0 needs to be set.
    $this->assertResult(1, 3, 5, 7, 9, 11, 13, 15);

    $this->queryResults = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition("$figures.1.color", 'red')
      ->sort('id')
      ->execute();
    // Delta 1 is not red.
    $this->assertResult();

    // Test on two different deltas.
    $query = $this->storage->getQuery()->accessCheck(FALSE);
    $or = $query->andConditionGroup()
      ->condition("$figures.0.color", 'red')
      ->condition("$figures.1.color", 'blue');
    $this->queryResults = $query
      ->condition($or)
      ->sort('id')
      ->execute();
    $this->assertResult(3, 7, 11, 15);

    // Test the delta range condition.
    $this->queryResults = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition("$figures.%delta.color", ['blue', 'red'], 'IN')
      ->condition("$figures.%delta", [0, 1], 'IN')
      ->sort('id')
      ->execute();
    // Figure delta 0 or 1 can be blue or red, this matches a lot of entities.
    $this->assertResult(1, 2, 3, 5, 6, 7, 9, 10, 11, 13, 14, 15);

    // Test the delta range condition without conditions on the value.
    $this->queryResults = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition("$figures.%delta", 1)
      ->sort('id')
      ->execute();
    // Entity needs to have at least two figures.
    $this->assertResult(3, 7, 11, 15);

    // Numeric delta on single value base field should return results only if
    // the first item is being targeted.
    $this->queryResults = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition("id.0.value", [1, 3, 5], 'IN')
      ->sort('id')
      ->execute();
    $this->assertResult(1, 3, 5);
    $this->queryResults = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition("id.1.value", [1, 3, 5], 'IN')
      ->sort('id')
      ->execute();
    $this->assertResult();

    // Delta range condition on single value base field should return results
    // only if just the field value is targeted.
    $this->queryResults = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition("id.%delta.value", [1, 3, 5], 'IN')
      ->sort('id')
      ->execute();
    $this->assertResult(1, 3, 5);
    $this->queryResults = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition("id.%delta.value", [1, 3, 5], 'IN')
      ->condition("id.%delta", 0, '=')
      ->sort('id')
      ->execute();
    $this->assertResult(1, 3, 5);
    $this->queryResults = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition("id.%delta.value", [1, 3, 5], 'IN')
      ->condition("id.%delta", 1, '=')
      ->sort('id')
      ->execute();
    $this->assertResult();

  }

  /**
   * @internal
   */
  protected function assertResult(): void {
    $assert = [];
    $expected = func_get_args();
    if ($expected && is_array($expected[0])) {
      $expected = $expected[0];
    }
    foreach ($expected as $binary) {
      $assert[$binary] = strval($binary);
    }
    $this->assertSame($assert, $this->queryResults);
  }

  /**
   * @internal
   */
  protected function assertRevisionResult(array $keys, array $expected): void {
    $assert = [];
    foreach ($expected as $key => $binary) {
      $assert[$keys[$key]] = strval($binary);
    }
    $this->assertSame($assert, $this->queryResults);
  }

  /**
   * @internal
   */
  protected function assertBundleOrder(string $order): void {
    // This loop is for bundle1 entities.
    for ($i = 1; $i <= 15; $i += 2) {
      $ok = TRUE;
      $index1 = array_search($i, $this->queryResults);
      $this->assertNotFalse($index1, "$i found at $index1.");
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
   * Tests adding a tag and metadata to the Entity query object.
   *
   * The tags and metadata should propagate to the SQL query object.
   */
  public function testMetaData() {
    field_test_memorize();

    $query = $this->storage->getQuery()->accessCheck(FALSE);
    $query
      ->addTag('efq_metadata_test')
      ->addMetaData('foo', 'bar')
      ->execute();

    $mem = field_test_memorize();
    $this->assertEquals('bar', $mem['field_test_query_efq_metadata_test_alter'][0], 'Tag and metadata propagated to the SQL query object.');
  }

  /**
   * Tests case sensitive and in-sensitive query conditions.
   */
  public function testCaseSensitivity() {
    $bundle = $this->randomMachineName();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_ci',
      'entity_type' => 'entity_test_mulrev',
      'type' => 'string',
      'cardinality' => 1,
      'translatable' => FALSE,
      'settings' => [
        'case_sensitive' => FALSE,
      ],
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $bundle,
    ])->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_cs',
      'entity_type' => 'entity_test_mulrev',
      'type' => 'string',
      'cardinality' => 1,
      'translatable' => FALSE,
      'settings' => [
        'case_sensitive' => TRUE,
      ],
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $bundle,
    ])->save();

    $fixtures = [];

    for ($i = 0; $i < 2; $i++) {
      // If the last 4 of the string are all numbers, then there is no
      // difference between upper and lowercase and the case sensitive CONTAINS
      // test will fail. Ensure that can not happen by appending a non-numeric
      // character. See https://www.drupal.org/node/2397297.
      $string = $this->randomMachineName(7) . 'a';
      $fixtures[] = [
        'original' => $string,
        'uppercase' => mb_strtoupper($string),
        'lowercase' => mb_strtolower($string),
      ];
    }

    EntityTestMulRev::create([
      'type' => $bundle,
      'name' => $this->randomMachineName(),
      'langcode' => 'en',
      'field_ci' => $fixtures[0]['uppercase'] . $fixtures[1]['lowercase'],
      'field_cs' => $fixtures[0]['uppercase'] . $fixtures[1]['lowercase'],
    ])->save();

    // Check the case insensitive field, = operator.
    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_ci', $fixtures[0]['lowercase'] . $fixtures[1]['lowercase'])
      ->execute();
    $this->assertCount(1, $result, 'Case insensitive, lowercase');

    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_ci', $fixtures[0]['uppercase'] . $fixtures[1]['uppercase'])
      ->execute();
    $this->assertCount(1, $result, 'Case insensitive, uppercase');

    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_ci', $fixtures[0]['uppercase'] . $fixtures[1]['lowercase'])
      ->execute();
    $this->assertCount(1, $result, 'Case insensitive, mixed.');

    // Check the case sensitive field, = operator.
    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_cs', $fixtures[0]['lowercase'] . $fixtures[1]['lowercase'])
      ->execute();
    $this->assertCount(0, $result, 'Case sensitive, lowercase.');

    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_cs', $fixtures[0]['uppercase'] . $fixtures[1]['uppercase'])
      ->execute();
    $this->assertCount(0, $result, 'Case sensitive, uppercase.');

    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_cs', $fixtures[0]['uppercase'] . $fixtures[1]['lowercase'])
      ->execute();
    $this->assertCount(1, $result, 'Case sensitive, exact match.');

    // Check the case insensitive field, IN operator.
    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_ci', [$fixtures[0]['lowercase'] . $fixtures[1]['lowercase']], 'IN')
      ->execute();
    $this->assertCount(1, $result, 'Case insensitive, lowercase');

    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_ci', [$fixtures[0]['uppercase'] . $fixtures[1]['uppercase']], 'IN')->execute();
    $this->assertCount(1, $result, 'Case insensitive, uppercase');

    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_ci', [$fixtures[0]['uppercase'] . $fixtures[1]['lowercase']], 'IN')
      ->execute();
    $this->assertCount(1, $result, 'Case insensitive, mixed');

    // Check the case sensitive field, IN operator.
    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_cs', [$fixtures[0]['lowercase'] . $fixtures[1]['lowercase']], 'IN')
      ->execute();
    $this->assertCount(0, $result, 'Case sensitive, lowercase');

    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_cs', [$fixtures[0]['uppercase'] . $fixtures[1]['uppercase']], 'IN')
      ->execute();
    $this->assertCount(0, $result, 'Case sensitive, uppercase');

    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_cs', [$fixtures[0]['uppercase'] . $fixtures[1]['lowercase']], 'IN')
      ->execute();
    $this->assertCount(1, $result, 'Case sensitive, mixed');

    // Check the case insensitive field, STARTS_WITH operator.
    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_ci', $fixtures[0]['lowercase'], 'STARTS_WITH')
      ->execute();
    $this->assertCount(1, $result, 'Case sensitive, lowercase.');

    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_ci', $fixtures[0]['uppercase'], 'STARTS_WITH')
      ->execute();
    $this->assertCount(1, $result, 'Case sensitive, exact match.');

    // Check the case sensitive field, STARTS_WITH operator.
    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_cs', $fixtures[0]['lowercase'], 'STARTS_WITH')
      ->execute();
    $this->assertCount(0, $result, 'Case sensitive, lowercase.');

    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_cs', $fixtures[0]['uppercase'], 'STARTS_WITH')
      ->execute();
    $this->assertCount(1, $result, 'Case sensitive, exact match.');

    // Check the case insensitive field, ENDS_WITH operator.
    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_ci', $fixtures[1]['lowercase'], 'ENDS_WITH')
      ->execute();
    $this->assertCount(1, $result, 'Case sensitive, lowercase.');

    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_ci', $fixtures[1]['uppercase'], 'ENDS_WITH')
      ->execute();
    $this->assertCount(1, $result, 'Case sensitive, exact match.');

    // Check the case sensitive field, ENDS_WITH operator.
    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_cs', $fixtures[1]['lowercase'], 'ENDS_WITH')
      ->execute();
    $this->assertCount(1, $result, 'Case sensitive, lowercase.');

    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_cs', $fixtures[1]['uppercase'], 'ENDS_WITH')
      ->execute();
    $this->assertCount(0, $result, 'Case sensitive, exact match.');

    // Check the case insensitive field, CONTAINS operator, use the inner 8
    // characters of the uppercase and lowercase strings.
    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_ci', mb_substr($fixtures[0]['uppercase'] . $fixtures[1]['lowercase'], 4, 8), 'CONTAINS')
      ->execute();
    $this->assertCount(1, $result, 'Case sensitive, lowercase.');

    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_ci', mb_strtolower(mb_substr($fixtures[0]['uppercase'] . $fixtures[1]['lowercase'], 4, 8)), 'CONTAINS')
      ->execute();
    $this->assertCount(1, $result, 'Case sensitive, exact match.');

    // Check the case sensitive field, CONTAINS operator.
    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_cs', mb_substr($fixtures[0]['uppercase'] . $fixtures[1]['lowercase'], 4, 8), 'CONTAINS')
      ->execute();
    $this->assertCount(1, $result, 'Case sensitive, lowercase.');

    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_cs', mb_strtolower(mb_substr($fixtures[0]['uppercase'] . $fixtures[1]['lowercase'], 4, 8)), 'CONTAINS')
      ->execute();
    $this->assertCount(0, $result, 'Case sensitive, exact match.');

  }

  /**
   * Tests base fields with multiple columns.
   */
  public function testBaseFieldMultipleColumns() {
    $this->enableModules(['taxonomy']);
    $this->installEntitySchema('taxonomy_term');

    Vocabulary::create(['vid' => 'tags']);

    $term1 = Term::create([
      'name' => $this->randomMachineName(),
      'vid' => 'tags',
      'description' => [
        'value' => 'description1',
        'format' => 'format1',
      ],
    ]);
    $term1->save();

    $term2 = Term::create([
      'name' => $this->randomMachineName(),
      'vid' => 'tags',
      'description' => [
        'value' => 'description2',
        'format' => 'format2',
      ],
    ]);
    $term2->save();

    // Test that the properties can be queried directly.
    $ids = $this->container->get('entity_type.manager')
      ->getStorage('taxonomy_term')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('description.value', 'description1')
      ->execute();
    $this->assertCount(1, $ids);
    $this->assertEquals($term1->id(), reset($ids));

    $ids = $this->container->get('entity_type.manager')
      ->getStorage('taxonomy_term')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('description.format', 'format1')
      ->execute();
    $this->assertCount(1, $ids);
    $this->assertEquals($term1->id(), reset($ids));

    // Test that the main property is queried if no property is specified.
    $ids = $this->container->get('entity_type.manager')
      ->getStorage('taxonomy_term')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('description', 'description1')
      ->execute();
    $this->assertCount(1, $ids);
    $this->assertEquals($term1->id(), reset($ids));
  }

  /**
   * Tests pending revisions.
   */
  public function testPendingRevisions() {
    // Ensure entity 14 is returned.
    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('id', [14], 'IN')
      ->execute();
    $this->assertCount(1, $result);

    // Set a revision on entity 14 that isn't the current default.
    $entity = EntityTestMulRev::load(14);
    $current_values = $entity->{$this->figures}->getValue();

    $entity->setNewRevision(TRUE);
    $entity->isDefaultRevision(FALSE);
    $entity->{$this->figures}->setValue([
      'color' => 'red',
      'shape' => 'square',
    ]);
    $entity->save();

    // Entity query should still return entity 14.
    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('id', [14], 'IN')
      ->execute();
    $this->assertCount(1, $result);

    // Verify that field conditions on the default and pending revision are
    // work as expected.
    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('id', [14], 'IN')
      ->condition("$this->figures.color", $current_values[0]['color'])
      ->execute();
    $this->assertEquals([14 => '14'], $result);
    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('id', [14], 'IN')
      ->condition("$this->figures.color", 'red')
      ->allRevisions()
      ->execute();
    $this->assertEquals([16 => '14'], $result);

    // Add another pending revision on the same entity and repeat the checks.
    $entity->setNewRevision(TRUE);
    $entity->isDefaultRevision(FALSE);
    $entity->{$this->figures}->setValue([
      'color' => 'red',
      'shape' => 'square',
    ]);
    $entity->save();

    // A non-revisioned entity query should still return entity 14.
    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('id', [14], 'IN')
      ->execute();
    $this->assertCount(1, $result);
    $this->assertSame([14 => '14'], $result);

    // Now check an entity query on the latest revision.
    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('id', [14], 'IN')
      ->latestRevision()
      ->execute();
    $this->assertCount(1, $result);
    $this->assertSame([17 => '14'], $result);

    // Verify that field conditions on the default and pending revision still
    // work as expected.
    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('id', [14], 'IN')
      ->condition("$this->figures.color", $current_values[0]['color'])
      ->execute();
    $this->assertSame([14 => '14'], $result);

    // Now there are two revisions with same value for the figure color.
    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('id', [14], 'IN')
      ->condition("$this->figures.color", 'red')
      ->allRevisions()
      ->execute();
    $this->assertSame([16 => '14', 17 => '14'], $result);

    // Check that querying for the latest revision returns the correct one.
    $result = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('id', [14], 'IN')
      ->condition("$this->figures.color", 'red')
      ->latestRevision()
      ->execute();
    $this->assertSame([17 => '14'], $result);
  }

  /**
   * Tests against SQL inject of condition field. This covers a
   * database driver's EntityQuery\Condition class.
   */
  public function testInjectionInCondition() {
    $this->expectException(\Exception::class);
    $this->queryResults = $this->storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('1 ; -- ', [0, 1], 'IN')
      ->sort('id')
      ->execute();
  }

  /**
   * Tests that EntityQuery works when querying the same entity from two fields.
   */
  public function testWithTwoEntityReferenceFieldsToSameEntityType() {
    // Create two entity reference fields referring 'entity_test' entities.
    $this->createEntityReferenceField('entity_test', 'entity_test', 'ref1', $this->randomMachineName(), 'entity_test');
    $this->createEntityReferenceField('entity_test', 'entity_test', 'ref2', $this->randomMachineName(), 'entity_test');

    $storage = $this->container->get('entity_type.manager')
      ->getStorage('entity_test');

    // Create two entities to be referred.
    $ref1 = EntityTest::create(['type' => 'entity_test']);
    $ref1->save();
    $ref2 = EntityTest::create(['type' => 'entity_test']);
    $ref2->save();

    // Create a main entity referring the previous created entities.
    $entity = EntityTest::create([
      'type' => 'entity_test',
      'ref1' => $ref1->id(),
      'ref2' => $ref2->id(),
    ]);
    $entity->save();

    // Check that works when referring with "{$field_name}".
    $result = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'entity_test')
      ->condition('ref1', $ref1->id())
      ->condition('ref2', $ref2->id())
      ->execute();
    $this->assertCount(1, $result);
    $this->assertEquals($entity->id(), reset($result));

    // Check that works when referring with "{$field_name}.target_id".
    $result = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'entity_test')
      ->condition('ref1.target_id', $ref1->id())
      ->condition('ref2.target_id', $ref2->id())
      ->execute();
    $this->assertCount(1, $result);
    $this->assertEquals($entity->id(), reset($result));

    // Check that works when referring with "{$field_name}.entity.id".
    $result = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'entity_test')
      ->condition('ref1.entity.id', $ref1->id())
      ->condition('ref2.entity.id', $ref2->id())
      ->execute();
    $this->assertCount(1, $result);
    $this->assertEquals($entity->id(), reset($result));
  }

  /**
   * Tests entity queries with condition on the revision metadata keys.
   */
  public function testConditionOnRevisionMetadataKeys() {
    $this->installModule('entity_test_revlog');
    $this->installEntitySchema('entity_test_revlog');

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    /** @var \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type */
    $entity_type = $entity_type_manager->getDefinition('entity_test_revlog');
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $entity_type_manager->getStorage('entity_test_revlog');

    $revision_created_timestamp = time();
    $revision_created_field_name = $entity_type->getRevisionMetadataKey('revision_created');
    $entity = $storage->create([
      'type' => 'entity_test',
      $revision_created_field_name => $revision_created_timestamp,
    ]);
    $entity->save();

    // Query only the default revision.
    $result = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition($revision_created_field_name, $revision_created_timestamp)
      ->execute();
    $this->assertCount(1, $result);
    $this->assertEquals($entity->id(), reset($result));

    // Query all revisions.
    $result = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition($revision_created_field_name, $revision_created_timestamp)
      ->allRevisions()
      ->execute();
    $this->assertCount(1, $result);
    $this->assertEquals($entity->id(), reset($result));
  }

  /**
   * Tests __toString().
   */
  public function testToString() {
    $query = $this->storage->getQuery()->accessCheck(FALSE);
    $group_blue = $query->andConditionGroup()->condition("{$this->figures}.color", ['blue'], 'IN');
    $group_red = $query->andConditionGroup()->condition("{$this->figures}.color", ['red'], 'IN');
    $null_group = $query->andConditionGroup()->notExists("{$this->figures}.color");
    $this->queryResults = $query
      ->condition($group_blue)
      ->condition($group_red)
      ->condition($null_group)
      ->sort('id');

    $figures = $this->figures;

    // Matching the SQL statement against an hardcoded statement leads to
    // failures with database drivers that override the
    // Drupal\Core\Database\Query\Select class. We build a dynamic query via
    // the db API to check that its SQL matches the one generated by the
    // EntityQuery. This way we ensure that the database driver is free to
    // create its own comparable SQL statement.
    $connection = Database::getConnection();
    $expected = $connection->select("entity_test_mulrev", "base_table");
    $expected->addField("base_table", "revision_id", "revision_id");
    $expected->addField("base_table", "id", "id");
    $expected->join("entity_test_mulrev__$figures", "entity_test_mulrev__$figures", '[entity_test_mulrev__' . $figures . '].[entity_id] = [base_table].[id]');
    $expected->join("entity_test_mulrev__$figures", "entity_test_mulrev__{$figures}_2", '[entity_test_mulrev__' . $figures . '_2].[entity_id] = [base_table].[id]');
    $expected->addJoin("LEFT", "entity_test_mulrev__$figures", "entity_test_mulrev__{$figures}_3", '[entity_test_mulrev__' . $figures . '_3].[entity_id] = [base_table].[id]');
    $expected->condition("entity_test_mulrev__$figures.{$figures}_color", ["blue"], "IN");
    $expected->condition("entity_test_mulrev__{$figures}_2.{$figures}_color", ["red"], "IN");
    $expected->isNull("entity_test_mulrev__{$figures}_3.{$figures}_color");
    $expected->orderBy("base_table.id");

    // Apply table prefixes and quote identifiers for the expected SQL.
    $expected_string = $connection->prefixTables((string) $expected);
    $expected_string = $connection->quoteIdentifiers($expected_string);
    // Resolve placeholders in the expected SQL to their values.
    $quoted = [];
    foreach ($expected->getArguments() as $key => $value) {
      $quoted[$key] = $connection->quote($value);
    }
    $expected_string = strtr($expected_string, $quoted);

    $this->assertSame($expected_string, (string) $query);
  }

  /**
   * Test the accessCheck method is called.
   *
   * @group legacy
   */
  public function testAccessCheckSpecified() {
    $this->expectDeprecation('Relying on entity queries to check access by default is deprecated in drupal:9.2.0 and an error will be thrown from drupal:10.0.0. Call \Drupal\Core\Entity\Query\QueryInterface::accessCheck() with TRUE or FALSE to specify whether access should be checked. See https://www.drupal.org/node/3201242');
    $this->storage->getQuery()->execute();
  }

}

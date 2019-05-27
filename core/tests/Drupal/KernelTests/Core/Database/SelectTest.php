<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\InvalidQueryException;
use Drupal\Core\Database\Database;

/**
 * Tests the Select query builder.
 *
 * @group Database
 */
class SelectTest extends DatabaseTestBase {

  /**
   * Tests rudimentary SELECT statements.
   */
  public function testSimpleSelect() {
    $query = $this->connection->select('test');
    $query->addField('test', 'name');
    $query->addField('test', 'age', 'age');
    $num_records = $query->countQuery()->execute()->fetchField();

    $this->assertEqual($num_records, 4, 'Returned the correct number of rows.');
  }

  /**
   * Tests rudimentary SELECT statement with a COMMENT.
   */
  public function testSimpleComment() {
    $query = $this->connection->select('test')->comment('Testing query comments');
    $query->addField('test', 'name');
    $query->addField('test', 'age', 'age');
    $result = $query->execute();

    $records = $result->fetchAll();

    $query = (string) $query;
    $expected = "/* Testing query comments */";

    $this->assertEqual(count($records), 4, 'Returned the correct number of rows.');
    $this->assertNotIdentical(FALSE, strpos($query, $expected), 'The flattened query contains the comment string.');
  }

  /**
   * Tests query COMMENT system against vulnerabilities.
   */
  public function testVulnerableComment() {
    $query = $this->connection->select('test')->comment('Testing query comments */ SELECT nid FROM {node}; --');
    $query->addField('test', 'name');
    $query->addField('test', 'age', 'age');
    $result = $query->execute();

    $records = $result->fetchAll();

    $query = (string) $query;
    $expected = "/* Testing query comments  * / SELECT nid FROM {node}. -- */ SELECT test.name AS name, test.age AS age\nFROM\n{test} test";

    $this->assertEqual(count($records), 4, 'Returned the correct number of rows.');
    $this->assertNotIdentical(FALSE, strpos($query, $expected), 'The flattened query contains the sanitised comment string.');

    $connection = Database::getConnection();
    foreach ($this->makeCommentsProvider() as $test_set) {
      list($expected, $comments) = $test_set;
      $this->assertEqual($expected, $connection->makeComment($comments));
    }
  }

  /**
   * Provides expected and input values for testVulnerableComment().
   */
  public function makeCommentsProvider() {
    return [
      [
        '/*  */ ',
        [''],
      ],
      // Try and close the comment early.
      [
        '/* Exploit  * / DROP TABLE node. -- */ ',
        ['Exploit */ DROP TABLE node; --'],
      ],
      // Variations on comment closing.
      [
        '/* Exploit  * / * / DROP TABLE node. -- */ ',
        ['Exploit */*/ DROP TABLE node; --'],
      ],
      [
        '/* Exploit  *  * // DROP TABLE node. -- */ ',
        ['Exploit **// DROP TABLE node; --'],
      ],
      // Try closing the comment in the second string which is appended.
      [
        '/* Exploit  * / DROP TABLE node. --. Another try  * / DROP TABLE node. -- */ ',
        ['Exploit */ DROP TABLE node; --', 'Another try */ DROP TABLE node; --'],
      ],
    ];
  }

  /**
   * Tests basic conditionals on SELECT statements.
   */
  public function testSimpleSelectConditional() {
    $query = $this->connection->select('test');
    $name_field = $query->addField('test', 'name');
    $age_field = $query->addField('test', 'age', 'age');
    $query->condition('age', 27);
    $result = $query->execute();

    // Check that the aliases are being created the way we want.
    $this->assertEqual($name_field, 'name', 'Name field alias is correct.');
    $this->assertEqual($age_field, 'age', 'Age field alias is correct.');

    // Ensure that we got the right record.
    $record = $result->fetch();
    $this->assertEqual($record->$name_field, 'George', 'Fetched name is correct.');
    $this->assertEqual($record->$age_field, 27, 'Fetched age is correct.');
  }

  /**
   * Tests SELECT statements with expressions.
   */
  public function testSimpleSelectExpression() {
    $query = $this->connection->select('test');
    $name_field = $query->addField('test', 'name');
    $age_field = $query->addExpression("age*2", 'double_age');
    $query->condition('age', 27);
    $result = $query->execute();

    // Check that the aliases are being created the way we want.
    $this->assertEqual($name_field, 'name', 'Name field alias is correct.');
    $this->assertEqual($age_field, 'double_age', 'Age field alias is correct.');

    // Ensure that we got the right record.
    $record = $result->fetch();
    $this->assertEqual($record->$name_field, 'George', 'Fetched name is correct.');
    $this->assertEqual($record->$age_field, 27 * 2, 'Fetched age expression is correct.');
  }

  /**
   * Tests SELECT statements with multiple expressions.
   */
  public function testSimpleSelectExpressionMultiple() {
    $query = $this->connection->select('test');
    $name_field = $query->addField('test', 'name');
    $age_double_field = $query->addExpression("age*2");
    $age_triple_field = $query->addExpression("age*3");
    $query->condition('age', 27);
    $result = $query->execute();

    // Check that the aliases are being created the way we want.
    $this->assertEqual($age_double_field, 'expression', 'Double age field alias is correct.');
    $this->assertEqual($age_triple_field, 'expression_2', 'Triple age field alias is correct.');

    // Ensure that we got the right record.
    $record = $result->fetch();
    $this->assertEqual($record->$name_field, 'George', 'Fetched name is correct.');
    $this->assertEqual($record->$age_double_field, 27 * 2, 'Fetched double age expression is correct.');
    $this->assertEqual($record->$age_triple_field, 27 * 3, 'Fetched triple age expression is correct.');
  }

  /**
   * Tests adding multiple fields to a SELECT statement at the same time.
   */
  public function testSimpleSelectMultipleFields() {
    $record = $this->connection->select('test')
      ->fields('test', ['id', 'name', 'age', 'job'])
      ->condition('age', 27)
      ->execute()->fetchObject();

    // Check that all fields we asked for are present.
    $this->assertNotNull($record->id, 'ID field is present.');
    $this->assertNotNull($record->name, 'Name field is present.');
    $this->assertNotNull($record->age, 'Age field is present.');
    $this->assertNotNull($record->job, 'Job field is present.');

    // Ensure that we got the right record.
    // Check that all fields we asked for are present.
    $this->assertEqual($record->id, 2, 'ID field has the correct value.');
    $this->assertEqual($record->name, 'George', 'Name field has the correct value.');
    $this->assertEqual($record->age, 27, 'Age field has the correct value.');
    $this->assertEqual($record->job, 'Singer', 'Job field has the correct value.');
  }

  /**
   * Tests adding all fields from a given table to a SELECT statement.
   */
  public function testSimpleSelectAllFields() {
    $record = $this->connection->select('test')
      ->fields('test')
      ->condition('age', 27)
      ->execute()->fetchObject();

    // Check that all fields we asked for are present.
    $this->assertNotNull($record->id, 'ID field is present.');
    $this->assertNotNull($record->name, 'Name field is present.');
    $this->assertNotNull($record->age, 'Age field is present.');
    $this->assertNotNull($record->job, 'Job field is present.');

    // Ensure that we got the right record.
    // Check that all fields we asked for are present.
    $this->assertEqual($record->id, 2, 'ID field has the correct value.');
    $this->assertEqual($record->name, 'George', 'Name field has the correct value.');
    $this->assertEqual($record->age, 27, 'Age field has the correct value.');
    $this->assertEqual($record->job, 'Singer', 'Job field has the correct value.');
  }

  /**
   * Tests that a comparison with NULL is always FALSE.
   */
  public function testNullCondition() {
    $this->ensureSampleDataNull();

    $names = $this->connection->select('test_null', 'tn')
      ->fields('tn', ['name'])
      ->condition('age', NULL)
      ->execute()->fetchCol();

    $this->assertEqual(count($names), 0, 'No records found when comparing to NULL.');
  }

  /**
   * Tests that we can find a record with a NULL value.
   */
  public function testIsNullCondition() {
    $this->ensureSampleDataNull();

    $names = $this->connection->select('test_null', 'tn')
      ->fields('tn', ['name'])
      ->isNull('age')
      ->execute()->fetchCol();

    $this->assertEqual(count($names), 1, 'Correct number of records found with NULL age.');
    $this->assertEqual($names[0], 'Fozzie', 'Correct record returned for NULL age.');
  }

  /**
   * Tests that we can find a record without a NULL value.
   */
  public function testIsNotNullCondition() {
    $this->ensureSampleDataNull();

    $names = $this->connection->select('test_null', 'tn')
      ->fields('tn', ['name'])
      ->isNotNull('tn.age')
      ->orderBy('name')
      ->execute()->fetchCol();

    $this->assertEqual(count($names), 2, 'Correct number of records found withNOT NULL age.');
    $this->assertEqual($names[0], 'Gonzo', 'Correct record returned for NOT NULL age.');
    $this->assertEqual($names[1], 'Kermit', 'Correct record returned for NOT NULL age.');
  }

  /**
   * Tests that we can force a query to return an empty result.
   */
  public function testAlwaysFalseCondition() {
    $names = $this->connection->select('test', 'test')
      ->fields('test', ['name'])
      ->condition('age', 27)
      ->execute()->fetchCol();

    $this->assertCount(1, $names);
    $this->assertSame($names[0], 'George');

    $names = $this->connection->select('test', 'test')
      ->fields('test', ['name'])
      ->condition('age', 27)
      ->alwaysFalse()
      ->execute()->fetchCol();

    $this->assertCount(0, $names);
  }

  /**
   * Tests that we can force an extended query to return an empty result.
   */
  public function testExtenderAlwaysFalseCondition() {
    $names = $this->connection->select('test', 'test')
      ->extend('Drupal\Core\Database\Query\SelectExtender')
      ->fields('test', ['name'])
      ->condition('age', 27)
      ->execute()->fetchCol();

    $this->assertCount(1, $names);
    $this->assertSame($names[0], 'George');

    $names = $this->connection->select('test', 'test')
      ->extend('Drupal\Core\Database\Query\SelectExtender')
      ->fields('test', ['name'])
      ->condition('age', 27)
      ->alwaysFalse()
      ->execute()->fetchCol();

    $this->assertCount(0, $names);
  }

  /**
   * Tests that we can UNION multiple Select queries together.
   *
   * This is semantically equal to UNION DISTINCT, so we don't explicitly test
   * that.
   */
  public function testUnion() {
    $query_1 = $this->connection->select('test', 't')
      ->fields('t', ['name'])
      ->condition('age', [27, 28], 'IN');

    $query_2 = $this->connection->select('test', 't')
      ->fields('t', ['name'])
      ->condition('age', 28);

    $query_1->union($query_2);

    $names = $query_1->execute()->fetchCol();

    // Ensure we only get 2 records.
    $this->assertEqual(count($names), 2, 'UNION correctly discarded duplicates.');

    $this->assertEqual($names[0], 'George', 'First query returned correct name.');
    $this->assertEqual($names[1], 'Ringo', 'Second query returned correct name.');
  }

  /**
   * Tests that we can UNION ALL multiple SELECT queries together.
   */
  public function testUnionAll() {
    $query_1 = $this->connection->select('test', 't')
      ->fields('t', ['name'])
      ->condition('age', [27, 28], 'IN');

    $query_2 = $this->connection->select('test', 't')
      ->fields('t', ['name'])
      ->condition('age', 28);

    $query_1->union($query_2, 'ALL');

    $names = $query_1->execute()->fetchCol();

    // Ensure we get all 3 records.
    $this->assertEqual(count($names), 3, 'UNION ALL correctly preserved duplicates.');

    $this->assertEqual($names[0], 'George', 'First query returned correct first name.');
    $this->assertEqual($names[1], 'Ringo', 'Second query returned correct second name.');
    $this->assertEqual($names[2], 'Ringo', 'Third query returned correct name.');
  }

  /**
   * Tests that we can get a count query for a UNION Select query.
   */
  public function testUnionCount() {
    $query_1 = $this->connection->select('test', 't')
      ->fields('t', ['name', 'age'])
      ->condition('age', [27, 28], 'IN');

    $query_2 = $this->connection->select('test', 't')
      ->fields('t', ['name', 'age'])
      ->condition('age', 28);

    $query_1->union($query_2, 'ALL');
    $names = $query_1->execute()->fetchCol();

    $query_3 = $query_1->countQuery();
    $count = $query_3->execute()->fetchField();

    // Ensure the counts match.
    $this->assertEqual(count($names), $count, "The count query's result matched the number of rows in the UNION query.");
  }

  /**
   * Tests that we can UNION multiple Select queries together and set the ORDER.
   */
  public function testUnionOrder() {
    // This gives George and Ringo.
    $query_1 = $this->connection->select('test', 't')
      ->fields('t', ['name'])
      ->condition('age', [27, 28], 'IN');

    // This gives Paul.
    $query_2 = $this->connection->select('test', 't')
      ->fields('t', ['name'])
      ->condition('age', 26);

    $query_1->union($query_2);
    $query_1->orderBy('name', 'DESC');

    $names = $query_1->execute()->fetchCol();

    // Ensure we get all 3 records.
    $this->assertEqual(count($names), 3, 'UNION returned rows from both queries.');

    // Ensure that the names are in the correct reverse alphabetical order,
    // regardless of which query they came from.
    $this->assertEqual($names[0], 'Ringo', 'First query returned correct name.');
    $this->assertEqual($names[1], 'Paul', 'Second query returned correct name.');
    $this->assertEqual($names[2], 'George', 'Third query returned correct name.');
  }

  /**
   * Tests that we can UNION multiple Select queries together with and a LIMIT.
   */
  public function testUnionOrderLimit() {
    // This gives George and Ringo.
    $query_1 = $this->connection->select('test', 't')
      ->fields('t', ['name'])
      ->condition('age', [27, 28], 'IN');

    // This gives Paul.
    $query_2 = $this->connection->select('test', 't')
      ->fields('t', ['name'])
      ->condition('age', 26);

    $query_1->union($query_2);
    $query_1->orderBy('name', 'DESC');
    $query_1->range(0, 2);

    $names = $query_1->execute()->fetchCol();

    // Ensure we get all only 2 of the 3 records.
    $this->assertEqual(count($names), 2, 'UNION with a limit returned rows from both queries.');

    // Ensure that the names are in the correct reverse alphabetical order,
    // regardless of which query they came from.
    $this->assertEqual($names[0], 'Ringo', 'First query returned correct name.');
    $this->assertEqual($names[1], 'Paul', 'Second query returned correct name.');
  }

  /**
   * Tests that random ordering of queries works.
   *
   * We take the approach of testing the Drupal layer only, rather than trying
   * to test that the database's random number generator actually produces
   * random queries (which is very difficult to do without an unacceptable risk
   * of the test failing by accident).
   *
   * Therefore, in this test we simply run the same query twice and assert that
   * the two results are reordered versions of each other (as well as of the
   * same query without the random ordering). It is reasonable to assume that
   * if we run the same select query twice and the results are in a different
   * order each time, the only way this could happen is if we have successfully
   * triggered the database's random ordering functionality.
   */
  public function testRandomOrder() {
    // Use 52 items, so the chance that this test fails by accident will be the
    // same as the chance that a deck of cards will come out in the same order
    // after shuffling it (in other words, nearly impossible).
    $number_of_items = 52;
    while ($this->connection->query("SELECT MAX(id) FROM {test}")->fetchField() < $number_of_items) {
      $this->connection->insert('test')->fields(['name' => $this->randomMachineName()])->execute();
    }

    // First select the items in order and make sure we get an ordered list.
    $expected_ids = range(1, $number_of_items);
    $ordered_ids = $this->connection->select('test', 't')
      ->fields('t', ['id'])
      ->range(0, $number_of_items)
      ->orderBy('id')
      ->execute()
      ->fetchCol();
    $this->assertEqual($ordered_ids, $expected_ids, 'A query without random ordering returns IDs in the correct order.');

    // Now perform the same query, but instead choose a random ordering. We
    // expect this to contain a differently ordered version of the original
    // result.
    $randomized_ids = $this->connection->select('test', 't')
      ->fields('t', ['id'])
      ->range(0, $number_of_items)
      ->orderRandom()
      ->execute()
      ->fetchCol();
    $this->assertNotEqual($randomized_ids, $ordered_ids, 'A query with random ordering returns an unordered set of IDs.');
    $sorted_ids = $randomized_ids;
    sort($sorted_ids);
    $this->assertEqual($sorted_ids, $ordered_ids, 'After sorting the random list, the result matches the original query.');

    // Now perform the exact same query again, and make sure the order is
    // different.
    $randomized_ids_second_set = $this->connection->select('test', 't')
      ->fields('t', ['id'])
      ->range(0, $number_of_items)
      ->orderRandom()
      ->execute()
      ->fetchCol();
    $this->assertNotEqual($randomized_ids_second_set, $randomized_ids, 'Performing the query with random ordering a second time returns IDs in a different order.');
    $sorted_ids_second_set = $randomized_ids_second_set;
    sort($sorted_ids_second_set);
    $this->assertEqual($sorted_ids_second_set, $sorted_ids, 'After sorting the second random list, the result matches the sorted version of the first random list.');
  }

  /**
   * Tests that filter by a regular expression works as expected.
   */
  public function testRegexCondition() {

    $test_groups[] = [
      'regex' => 'hn$',
      'expected' => [
        'John',
      ],
    ];
    $test_groups[] = [
      'regex' => '^Pau',
      'expected' => [
        'Paul',
      ],
    ];
    $test_groups[] = [
      'regex' => 'Ringo|George',
      'expected' => [
        'Ringo', 'George',
      ],
    ];

    $database = $this->container->get('database');
    foreach ($test_groups as $test_group) {
      $query = $database->select('test', 't');
      $query->addField('t', 'name');
      $query->condition('t.name', $test_group['regex'], 'REGEXP');
      $result = $query->execute()->fetchCol();

      $this->assertEqual(count($result), count($test_group['expected']), 'Returns the expected number of rows.');
      $this->assertEqual(sort($result), sort($test_group['expected']), 'Returns the expected rows.');
    }

    // Ensure that filter by "#" still works due to the quoting.
    $database->insert('test')
      ->fields([
        'name' => 'Pete',
        'age' => 26,
        'job' => '#Drummer',
      ])
      ->execute();

    $test_groups = [];
    $test_groups[] = [
      'regex' => '#Drummer',
      'expected' => [
        'Pete',
      ],
    ];
    $test_groups[] = [
      'regex' => '#Singer',
      'expected' => [],
    ];

    foreach ($test_groups as $test_group) {
      $query = $database->select('test', 't');
      $query->addField('t', 'name');
      $query->condition('t.job', $test_group['regex'], 'REGEXP');
      $result = $query->execute()->fetchCol();

      $this->assertEqual(count($result), count($test_group['expected']), 'Returns the expected number of rows.');
      $this->assertEqual(sort($result), sort($test_group['expected']), 'Returns the expected rows.');
    }

    // Ensure that REGEXP filter still works with no-string type field.
    $query = $database->select('test', 't');
    $query->addField('t', 'age');
    $query->condition('t.age', '2[6]', 'REGEXP');
    $result = $query->execute()->fetchField();
    $this->assertEquals($result, '26', 'Regexp with number type.');
  }

  /**
   * Tests that aliases are renamed when they are duplicates.
   */
  public function testSelectDuplicateAlias() {
    $query = $this->connection->select('test', 't');
    $alias1 = $query->addField('t', 'name', 'the_alias');
    $alias2 = $query->addField('t', 'age', 'the_alias');
    $this->assertNotIdentical($alias1, $alias2, 'Duplicate aliases are renamed.');
  }

  /**
   * Tests that an invalid merge query throws an exception.
   */
  public function testInvalidSelectCount() {
    try {
      // This query will fail because the table does not exist.
      // Normally it would throw an exception but we are suppressing
      // it with the throw_exception option.
      $options['throw_exception'] = FALSE;
      $this->connection->select('some_table_that_doesnt_exist', 't', $options)
        ->fields('t')
        ->countQuery()
        ->execute();

      $this->pass('$options[\'throw_exception\'] is FALSE, no Exception thrown.');
    }
    catch (\Exception $e) {
      $this->fail('$options[\'throw_exception\'] is FALSE, but Exception thrown for invalid query.');
      return;
    }

    try {
      // This query will fail because the table does not exist.
      $this->connection->select('some_table_that_doesnt_exist', 't')
        ->fields('t')
        ->countQuery()
        ->execute();
    }
    catch (\Exception $e) {
      $this->pass('Exception thrown for invalid query.');
      return;
    }
    $this->fail('No Exception thrown.');
  }

  /**
   * Tests thrown exception for IN query conditions with an empty array.
   */
  public function testEmptyInCondition() {
    try {
      $this->connection->select('test', 't')
        ->fields('t')
        ->condition('age', [], 'IN')
        ->execute();

      $this->fail('Expected exception not thrown');
    }
    catch (InvalidQueryException $e) {
      $this->assertEqual("Query condition 'age IN ()' cannot be empty.", $e->getMessage());
    }

    try {
      $this->connection->select('test', 't')
        ->fields('t')
        ->condition('age', [], 'NOT IN')
        ->execute();

      $this->fail('Expected exception not thrown');
    }
    catch (InvalidQueryException $e) {
      $this->assertEqual("Query condition 'age NOT IN ()' cannot be empty.", $e->getMessage());
    }
  }

}

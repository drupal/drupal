<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\InvalidQueryException;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\Query\SelectExtender;

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

    $this->assertEquals(4, $num_records, 'Returned the correct number of rows.');
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

    $this->assertCount(4, $records, 'Returned the correct number of rows.');
    $this->assertStringContainsString($expected, $query, 'The flattened query contains the comment string.');
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
    $expected = "/* Testing query comments  * / SELECT nid FROM {node}. -- */";

    // Check the returned number of rows.
    $this->assertCount(4, $records);
    // Check that the flattened query contains the sanitized comment string.
    $this->assertStringContainsString($expected, $query);

    $connection = Database::getConnection();
    foreach ($this->makeCommentsProvider() as $test_set) {
      list($expected, $comments) = $test_set;
      $this->assertEquals($expected, $connection->makeComment($comments));
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
    $this->assertEquals('name', $name_field, 'Name field alias is correct.');
    $this->assertEquals('age', $age_field, 'Age field alias is correct.');

    // Ensure that we got the right record.
    $record = $result->fetch();
    $this->assertEquals('George', $record->{$name_field}, 'Fetched name is correct.');
    $this->assertEquals(27, $record->{$age_field}, 'Fetched age is correct.');
  }

  /**
   * Tests SELECT statements with expressions.
   */
  public function testSimpleSelectExpression() {
    $query = $this->connection->select('test');
    $name_field = $query->addField('test', 'name');
    $age_field = $query->addExpression("[age]*2", 'double_age');
    $query->condition('age', 27);
    $result = $query->execute();

    // Check that the aliases are being created the way we want.
    $this->assertEquals('name', $name_field, 'Name field alias is correct.');
    $this->assertEquals('double_age', $age_field, 'Age field alias is correct.');

    // Ensure that we got the right record.
    $record = $result->fetch();
    $this->assertEquals('George', $record->{$name_field}, 'Fetched name is correct.');
    $this->assertEquals(27 * 2, $record->{$age_field}, 'Fetched age expression is correct.');
  }

  /**
   * Tests SELECT statements with multiple expressions.
   */
  public function testSimpleSelectExpressionMultiple() {
    $query = $this->connection->select('test');
    $name_field = $query->addField('test', 'name');
    $age_double_field = $query->addExpression("[age]*2");
    $age_triple_field = $query->addExpression("[age]*3");
    $query->condition('age', 27);
    $result = $query->execute();

    // Check that the aliases are being created the way we want.
    $this->assertEquals('expression', $age_double_field, 'Double age field alias is correct.');
    $this->assertEquals('expression_2', $age_triple_field, 'Triple age field alias is correct.');

    // Ensure that we got the right record.
    $record = $result->fetch();
    $this->assertEquals('George', $record->{$name_field}, 'Fetched name is correct.');
    $this->assertEquals(27 * 2, $record->{$age_double_field}, 'Fetched double age expression is correct.');
    $this->assertEquals(27 * 3, $record->{$age_triple_field}, 'Fetched triple age expression is correct.');
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
    $this->assertEquals(2, $record->id, 'ID field has the correct value.');
    $this->assertEquals('George', $record->name, 'Name field has the correct value.');
    $this->assertEquals(27, $record->age, 'Age field has the correct value.');
    $this->assertEquals('Singer', $record->job, 'Job field has the correct value.');
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
    $this->assertEquals(2, $record->id, 'ID field has the correct value.');
    $this->assertEquals('George', $record->name, 'Name field has the correct value.');
    $this->assertEquals(27, $record->age, 'Age field has the correct value.');
    $this->assertEquals('Singer', $record->job, 'Job field has the correct value.');
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

    $this->assertCount(0, $names, 'No records found when comparing to NULL.');
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

    $this->assertCount(1, $names, 'Correct number of records found with NULL age.');
    $this->assertEquals('Fozzie', $names[0], 'Correct record returned for NULL age.');
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

    $this->assertCount(2, $names, 'Correct number of records found withNOT NULL age.');
    $this->assertEquals('Gonzo', $names[0], 'Correct record returned for NOT NULL age.');
    $this->assertEquals('Kermit', $names[1], 'Correct record returned for NOT NULL age.');
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
      ->extend(SelectExtender::class)
      ->fields('test', ['name'])
      ->condition('age', 27)
      ->execute()->fetchCol();

    $this->assertCount(1, $names);
    $this->assertSame($names[0], 'George');

    $names = $this->connection->select('test', 'test')
      ->extend(SelectExtender::class)
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
    $this->assertCount(2, $names, 'UNION correctly discarded duplicates.');

    $this->assertEqualsCanonicalizing(['George', 'Ringo'], $names);
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
    $this->assertCount(3, $names, 'UNION ALL correctly preserved duplicates.');

    $this->assertEquals('George', $names[0], 'First query returned correct first name.');
    $this->assertEquals('Ringo', $names[1], 'Second query returned correct second name.');
    $this->assertEquals('Ringo', $names[2], 'Third query returned correct name.');
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
    $count = (int) $query_1->countQuery()->execute()->fetchField();

    // Ensure the counts match.
    $this->assertSame(count($names), $count, "The count query's result matched the number of rows in the UNION query.");
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
    $this->assertCount(3, $names, 'UNION returned rows from both queries.');

    // Ensure that the names are in the correct reverse alphabetical order,
    // regardless of which query they came from.
    $this->assertEquals('Ringo', $names[0], 'First query returned correct name.');
    $this->assertEquals('Paul', $names[1], 'Second query returned correct name.');
    $this->assertEquals('George', $names[2], 'Third query returned correct name.');
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
    $this->assertCount(2, $names, 'UNION with a limit returned rows from both queries.');

    // Ensure that the names are in the correct reverse alphabetical order,
    // regardless of which query they came from.
    $this->assertEquals('Ringo', $names[0], 'First query returned correct name.');
    $this->assertEquals('Paul', $names[1], 'Second query returned correct name.');
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
    while ($this->connection->query("SELECT MAX([id]) FROM {test}")->fetchField() < $number_of_items) {
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
    $this->assertEquals($expected_ids, $ordered_ids, 'A query without random ordering returns IDs in the correct order.');

    // Now perform the same query, but instead choose a random ordering. We
    // expect this to contain a differently ordered version of the original
    // result.
    $randomized_ids = $this->connection->select('test', 't')
      ->fields('t', ['id'])
      ->range(0, $number_of_items)
      ->orderRandom()
      ->execute()
      ->fetchCol();
    $this->assertNotEquals($ordered_ids, $randomized_ids, 'A query with random ordering returns an unordered set of IDs.');
    $sorted_ids = $randomized_ids;
    sort($sorted_ids);
    $this->assertEquals($ordered_ids, $sorted_ids, 'After sorting the random list, the result matches the original query.');

    // Now perform the exact same query again, and make sure the order is
    // different.
    $randomized_ids_second_set = $this->connection->select('test', 't')
      ->fields('t', ['id'])
      ->range(0, $number_of_items)
      ->orderRandom()
      ->execute()
      ->fetchCol();
    $this->assertNotEquals($randomized_ids, $randomized_ids_second_set, 'Performing the query with random ordering a second time returns IDs in a different order.');
    $sorted_ids_second_set = $randomized_ids_second_set;
    sort($sorted_ids_second_set);
    $this->assertEquals($sorted_ids, $sorted_ids_second_set, 'After sorting the second random list, the result matches the sorted version of the first random list.');
  }

  /**
   * Data provider for testRegularExpressionCondition().
   *
   * @return array[]
   *   Returns data-set elements with:
   *     - the expected result of the query
   *     - the table column to do the search on.
   *     - the regular expression pattern to search for.
   *     - the regular expression operator 'REGEXP' or 'NOT REGEXP'.
   */
  public function providerRegularExpressionCondition() {
    return [
      [['John'], 'name', 'hn$', 'REGEXP'],
      [['Paul'], 'name', '^Pau', 'REGEXP'],
      [['George', 'Ringo'], 'name', 'Ringo|George', 'REGEXP'],
      [['Pete'], 'job', '#Drummer', 'REGEXP'],
      [[], 'job', '#Singer', 'REGEXP'],
      [['Paul', 'Pete'], 'age', '2[6]', 'REGEXP'],

      [['George', 'Paul', 'Pete', 'Ringo'], 'name', 'hn$', 'NOT REGEXP'],
      [['George', 'John', 'Pete', 'Ringo'], 'name', '^Pau', 'NOT REGEXP'],
      [['John', 'Paul', 'Pete'], 'name', 'Ringo|George', 'NOT REGEXP'],
      [['George', 'John', 'Paul', 'Ringo'], 'job', '#Drummer', 'NOT REGEXP'],
      [['George', 'John', 'Paul', 'Pete', 'Ringo'], 'job', '#Singer', 'NOT REGEXP'],
      [['George', 'John', 'Ringo'], 'age', '2[6]', 'NOT REGEXP'],
    ];
  }

  /**
   * Tests that filter by 'REGEXP' and 'NOT REGEXP' works as expected.
   *
   * @dataProvider providerRegularExpressionCondition
   */
  public function testRegularExpressionCondition($expected, $column, $pattern, $operator) {
    $database = $this->container->get('database');
    $database->insert('test')
      ->fields([
        'name' => 'Pete',
        'age' => 26,
        'job' => '#Drummer',
      ])
      ->execute();

    $query = $database->select('test', 't');
    $query->addField('t', 'name');
    $query->condition("t.$column", $pattern, $operator);
    $result = $query->execute()->fetchCol();
    sort($result);

    $this->assertEquals($expected, $result);
  }

  /**
   * Tests that aliases are renamed when they are duplicates.
   */
  public function testSelectDuplicateAlias() {
    $query = $this->connection->select('test', 't');
    $alias1 = $query->addField('t', 'name', 'the_alias');
    $alias2 = $query->addField('t', 'age', 'the_alias');
    $this->assertNotSame($alias1, $alias2, 'Duplicate aliases are renamed.');
  }

  /**
   * Tests deprecation of the 'throw_exception' option.
   *
   * @group legacy
   */
  public function testLegacyThrowExceptionOption(): void {
    $this->expectDeprecation("Passing a 'throw_exception' option to %AExceptionHandler::handleExecutionException is deprecated in drupal:9.2.0 and is removed in drupal:10.0.0. Always catch exceptions. See https://www.drupal.org/node/3201187");
    // This query will fail because the table does not exist.
    $this->assertNull($this->connection->select('some_table_that_does_not_exist', 't', ['throw_exception' => FALSE])
      ->fields('t')
      ->countQuery()
      ->execute()
    );
  }

  /**
   * Tests that an invalid count query throws an exception.
   */
  public function testInvalidSelectCount() {
    $this->expectException(DatabaseExceptionWrapper::class);
    // This query will fail because the table does not exist.
    $this->connection->select('some_table_that_does_not_exist', 't')
      ->fields('t')
      ->countQuery()
      ->execute();
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
      $this->assertEquals("Query condition 'age IN ()' cannot be empty.", $e->getMessage());
    }

    try {
      $this->connection->select('test', 't')
        ->fields('t')
        ->condition('age', [], 'NOT IN')
        ->execute();

      $this->fail('Expected exception not thrown');
    }
    catch (InvalidQueryException $e) {
      $this->assertEquals("Query condition 'age NOT IN ()' cannot be empty.", $e->getMessage());
    }
  }

}

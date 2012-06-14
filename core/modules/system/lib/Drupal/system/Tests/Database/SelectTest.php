<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\SelectTest.
 */

namespace Drupal\system\Tests\Database;

/**
 * Test the SELECT builder.
 */
class SelectTest extends DatabaseTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Select tests',
      'description' => 'Test the Select query builder.',
      'group' => 'Database',
    );
  }

  /**
   * Test rudimentary SELECT statements.
   */
  function testSimpleSelect() {
    $query = db_select('test');
    $name_field = $query->addField('test', 'name');
    $age_field = $query->addField('test', 'age', 'age');
    $result = $query->execute();

    $num_records = 0;
    foreach ($result as $record) {
      $num_records++;
    }

    $this->assertEqual($num_records, 4, t('Returned the correct number of rows.'));
  }

  /**
   * Test rudimentary SELECT statement with a COMMENT.
   */
  function testSimpleComment() {
    $query = db_select('test')->comment('Testing query comments');
    $name_field = $query->addField('test', 'name');
    $age_field = $query->addField('test', 'age', 'age');
    $result = $query->execute();

    $num_records = 0;
    foreach ($result as $record) {
      $num_records++;
    }

    $query = (string)$query;
    $expected = "/* Testing query comments */ SELECT test.name AS name, test.age AS age\nFROM \n{test} test";

    $this->assertEqual($num_records, 4, t('Returned the correct number of rows.'));
    $this->assertEqual($query, $expected, t('The flattened query contains the comment string.'));
  }

  /**
   * Test query COMMENT system against vulnerabilities.
   */
  function testVulnerableComment() {
    $query = db_select('test')->comment('Testing query comments */ SELECT nid FROM {node}; --');
    $name_field = $query->addField('test', 'name');
    $age_field = $query->addField('test', 'age', 'age');
    $result = $query->execute();

    $num_records = 0;
    foreach ($result as $record) {
      $num_records++;
    }

    $query = (string)$query;
    $expected = "/* Testing query comments SELECT nid FROM {node}; -- */ SELECT test.name AS name, test.age AS age\nFROM \n{test} test";

    $this->assertEqual($num_records, 4, t('Returned the correct number of rows.'));
    $this->assertEqual($query, $expected, t('The flattened query contains the sanitised comment string.'));
  }

  /**
   * Test basic conditionals on SELECT statements.
   */
  function testSimpleSelectConditional() {
    $query = db_select('test');
    $name_field = $query->addField('test', 'name');
    $age_field = $query->addField('test', 'age', 'age');
    $query->condition('age', 27);
    $result = $query->execute();

    // Check that the aliases are being created the way we want.
    $this->assertEqual($name_field, 'name', t('Name field alias is correct.'));
    $this->assertEqual($age_field, 'age', t('Age field alias is correct.'));

    // Ensure that we got the right record.
    $record = $result->fetch();
    $this->assertEqual($record->$name_field, 'George', t('Fetched name is correct.'));
    $this->assertEqual($record->$age_field, 27, t('Fetched age is correct.'));
  }

  /**
   * Test SELECT statements with expressions.
   */
  function testSimpleSelectExpression() {
    $query = db_select('test');
    $name_field = $query->addField('test', 'name');
    $age_field = $query->addExpression("age*2", 'double_age');
    $query->condition('age', 27);
    $result = $query->execute();

    // Check that the aliases are being created the way we want.
    $this->assertEqual($name_field, 'name', t('Name field alias is correct.'));
    $this->assertEqual($age_field, 'double_age', t('Age field alias is correct.'));

    // Ensure that we got the right record.
    $record = $result->fetch();
    $this->assertEqual($record->$name_field, 'George', t('Fetched name is correct.'));
    $this->assertEqual($record->$age_field, 27*2, t('Fetched age expression is correct.'));
  }

  /**
   * Test SELECT statements with multiple expressions.
   */
  function testSimpleSelectExpressionMultiple() {
    $query = db_select('test');
    $name_field = $query->addField('test', 'name');
    $age_double_field = $query->addExpression("age*2");
    $age_triple_field = $query->addExpression("age*3");
    $query->condition('age', 27);
    $result = $query->execute();

    // Check that the aliases are being created the way we want.
    $this->assertEqual($age_double_field, 'expression', t('Double age field alias is correct.'));
    $this->assertEqual($age_triple_field, 'expression_2', t('Triple age field alias is correct.'));

    // Ensure that we got the right record.
    $record = $result->fetch();
    $this->assertEqual($record->$name_field, 'George', t('Fetched name is correct.'));
    $this->assertEqual($record->$age_double_field, 27*2, t('Fetched double age expression is correct.'));
    $this->assertEqual($record->$age_triple_field, 27*3, t('Fetched triple age expression is correct.'));
  }

  /**
   * Test adding multiple fields to a select statement at the same time.
   */
  function testSimpleSelectMultipleFields() {
    $record = db_select('test')
      ->fields('test', array('id', 'name', 'age', 'job'))
      ->condition('age', 27)
      ->execute()->fetchObject();

    // Check that all fields we asked for are present.
    $this->assertNotNull($record->id, t('ID field is present.'));
    $this->assertNotNull($record->name, t('Name field is present.'));
    $this->assertNotNull($record->age, t('Age field is present.'));
    $this->assertNotNull($record->job, t('Job field is present.'));

    // Ensure that we got the right record.
    // Check that all fields we asked for are present.
    $this->assertEqual($record->id, 2, t('ID field has the correct value.'));
    $this->assertEqual($record->name, 'George', t('Name field has the correct value.'));
    $this->assertEqual($record->age, 27, t('Age field has the correct value.'));
    $this->assertEqual($record->job, 'Singer', t('Job field has the correct value.'));
  }

  /**
   * Test adding all fields from a given table to a select statement.
   */
  function testSimpleSelectAllFields() {
    $record = db_select('test')
      ->fields('test')
      ->condition('age', 27)
      ->execute()->fetchObject();

    // Check that all fields we asked for are present.
    $this->assertNotNull($record->id, t('ID field is present.'));
    $this->assertNotNull($record->name, t('Name field is present.'));
    $this->assertNotNull($record->age, t('Age field is present.'));
    $this->assertNotNull($record->job, t('Job field is present.'));

    // Ensure that we got the right record.
    // Check that all fields we asked for are present.
    $this->assertEqual($record->id, 2, t('ID field has the correct value.'));
    $this->assertEqual($record->name, 'George', t('Name field has the correct value.'));
    $this->assertEqual($record->age, 27, t('Age field has the correct value.'));
    $this->assertEqual($record->job, 'Singer', t('Job field has the correct value.'));
  }

  /**
   * Test that a comparison with NULL is always FALSE.
   */
  function testNullCondition() {
    $this->ensureSampleDataNull();

    $names = db_select('test_null', 'tn')
      ->fields('tn', array('name'))
      ->condition('age', NULL)
      ->execute()->fetchCol();

    $this->assertEqual(count($names), 0, t('No records found when comparing to NULL.'));
  }

  /**
   * Test that we can find a record with a NULL value.
   */
  function testIsNullCondition() {
    $this->ensureSampleDataNull();

    $names = db_select('test_null', 'tn')
      ->fields('tn', array('name'))
      ->isNull('age')
      ->execute()->fetchCol();

    $this->assertEqual(count($names), 1, t('Correct number of records found with NULL age.'));
    $this->assertEqual($names[0], 'Fozzie', t('Correct record returned for NULL age.'));
  }

  /**
   * Test that we can find a record without a NULL value.
   */
  function testIsNotNullCondition() {
    $this->ensureSampleDataNull();

    $names = db_select('test_null', 'tn')
      ->fields('tn', array('name'))
      ->isNotNull('tn.age')
      ->orderBy('name')
      ->execute()->fetchCol();

    $this->assertEqual(count($names), 2, t('Correct number of records found withNOT NULL age.'));
    $this->assertEqual($names[0], 'Gonzo', t('Correct record returned for NOT NULL age.'));
    $this->assertEqual($names[1], 'Kermit', t('Correct record returned for NOT NULL age.'));
  }

  /**
   * Test that we can UNION multiple Select queries together. This is
   * semantically equal to UNION DISTINCT, so we don't explicity test that.
   */
  function testUnion() {
    $query_1 = db_select('test', 't')
      ->fields('t', array('name'))
      ->condition('age', array(27, 28), 'IN');

    $query_2 = db_select('test', 't')
      ->fields('t', array('name'))
      ->condition('age', 28);

    $query_1->union($query_2);

    $names = $query_1->execute()->fetchCol();

    // Ensure we only get 2 records.
    $this->assertEqual(count($names), 2, t('UNION correctly discarded duplicates.'));

    $this->assertEqual($names[0], 'George', t('First query returned correct name.'));
    $this->assertEqual($names[1], 'Ringo', t('Second query returned correct name.'));
  }

  /**
   * Test that we can UNION ALL multiple Select queries together.
   */
  function testUnionAll() {
    $query_1 = db_select('test', 't')
      ->fields('t', array('name'))
      ->condition('age', array(27, 28), 'IN');

    $query_2 = db_select('test', 't')
      ->fields('t', array('name'))
      ->condition('age', 28);

    $query_1->union($query_2, 'ALL');

    $names = $query_1->execute()->fetchCol();

    // Ensure we get all 3 records.
    $this->assertEqual(count($names), 3, t('UNION ALL correctly preserved duplicates.'));

    $this->assertEqual($names[0], 'George', t('First query returned correct first name.'));
    $this->assertEqual($names[1], 'Ringo', t('Second query returned correct second name.'));
    $this->assertEqual($names[2], 'Ringo', t('Third query returned correct name.'));
  }

  /**
   * Test that random ordering of queries works.
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
  function testRandomOrder() {
    // Use 52 items, so the chance that this test fails by accident will be the
    // same as the chance that a deck of cards will come out in the same order
    // after shuffling it (in other words, nearly impossible).
    $number_of_items = 52;
    while (db_query("SELECT MAX(id) FROM {test}")->fetchField() < $number_of_items) {
      db_insert('test')->fields(array('name' => $this->randomName()))->execute();
    }

    // First select the items in order and make sure we get an ordered list.
    $expected_ids = range(1, $number_of_items);
    $ordered_ids = db_select('test', 't')
      ->fields('t', array('id'))
      ->range(0, $number_of_items)
      ->orderBy('id')
      ->execute()
      ->fetchCol();
    $this->assertEqual($ordered_ids, $expected_ids, t('A query without random ordering returns IDs in the correct order.'));

    // Now perform the same query, but instead choose a random ordering. We
    // expect this to contain a differently ordered version of the original
    // result.
    $randomized_ids = db_select('test', 't')
      ->fields('t', array('id'))
      ->range(0, $number_of_items)
      ->orderRandom()
      ->execute()
      ->fetchCol();
    $this->assertNotEqual($randomized_ids, $ordered_ids, t('A query with random ordering returns an unordered set of IDs.'));
    $sorted_ids = $randomized_ids;
    sort($sorted_ids);
    $this->assertEqual($sorted_ids, $ordered_ids, t('After sorting the random list, the result matches the original query.'));

    // Now perform the exact same query again, and make sure the order is
    // different.
    $randomized_ids_second_set = db_select('test', 't')
      ->fields('t', array('id'))
      ->range(0, $number_of_items)
      ->orderRandom()
      ->execute()
      ->fetchCol();
    $this->assertNotEqual($randomized_ids_second_set, $randomized_ids, t('Performing the query with random ordering a second time returns IDs in a different order.'));
    $sorted_ids_second_set = $randomized_ids_second_set;
    sort($sorted_ids_second_set);
    $this->assertEqual($sorted_ids_second_set, $sorted_ids, t('After sorting the second random list, the result matches the sorted version of the first random list.'));
  }

  /**
   * Test that aliases are renamed when duplicates.
   */
  function testSelectDuplicateAlias() {
    $query = db_select('test', 't');
    $alias1 = $query->addField('t', 'name', 'the_alias');
    $alias2 = $query->addField('t', 'age', 'the_alias');
    $this->assertNotIdentical($alias1, $alias2, 'Duplicate aliases are renamed.');
  }
}

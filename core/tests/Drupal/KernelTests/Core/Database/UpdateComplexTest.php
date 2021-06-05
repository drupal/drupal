<?php

namespace Drupal\KernelTests\Core\Database;

/**
 * Tests the Update query builder, complex queries.
 *
 * @group Database
 */
class UpdateComplexTest extends DatabaseTestBase {

  /**
   * Tests updates with OR conditionals.
   */
  public function testOrConditionUpdate() {
    $update = $this->connection->update('test')
      ->fields(['job' => 'Musician'])
      ->condition(($this->connection->condition('OR'))
        ->condition('name', 'John')
        ->condition('name', 'Paul')
      );
    $num_updated = $update->execute();
    $this->assertSame(2, $num_updated, 'Updated 2 records.');

    $num_matches = $this->connection->query('SELECT COUNT(*) FROM {test} WHERE [job] = :job', [':job' => 'Musician'])->fetchField();
    $this->assertSame('2', $num_matches, 'Updated fields successfully.');
  }

  /**
   * Tests WHERE IN clauses.
   */
  public function testInConditionUpdate() {
    $num_updated = $this->connection->update('test')
      ->fields(['job' => 'Musician'])
      ->condition('name', ['John', 'Paul'], 'IN')
      ->execute();
    $this->assertSame(2, $num_updated, 'Updated 2 records.');

    $num_matches = $this->connection->query('SELECT COUNT(*) FROM {test} WHERE [job] = :job', [':job' => 'Musician'])->fetchField();
    $this->assertSame('2', $num_matches, 'Updated fields successfully.');
  }

  /**
   * Tests WHERE NOT IN clauses.
   */
  public function testNotInConditionUpdate() {
    // The o is lowercase in the 'NoT IN' operator, to make sure the operators
    // work in mixed case.
    $num_updated = $this->connection->update('test')
      ->fields(['job' => 'Musician'])
      ->condition('name', ['John', 'Paul', 'George'], 'NoT IN')
      ->execute();
    $this->assertSame(1, $num_updated, 'Updated 1 record.');

    $num_matches = $this->connection->query('SELECT COUNT(*) FROM {test} WHERE [job] = :job', [':job' => 'Musician'])->fetchField();
    $this->assertSame('1', $num_matches, 'Updated fields successfully.');
  }

  /**
   * Tests BETWEEN conditional clauses.
   */
  public function testBetweenConditionUpdate() {
    $num_updated = $this->connection->update('test')
      ->fields(['job' => 'Musician'])
      ->condition('age', [25, 26], 'BETWEEN')
      ->execute();
    $this->assertSame(2, $num_updated, 'Updated 2 records.');

    $num_matches = $this->connection->query('SELECT COUNT(*) FROM {test} WHERE [job] = :job', [':job' => 'Musician'])->fetchField();
    $this->assertSame('2', $num_matches, 'Updated fields successfully.');
  }

  /**
   * Tests LIKE conditionals.
   */
  public function testLikeConditionUpdate() {
    $num_updated = $this->connection->update('test')
      ->fields(['job' => 'Musician'])
      ->condition('name', '%ge%', 'LIKE')
      ->execute();
    $this->assertSame(1, $num_updated, 'Updated 1 record.');

    $num_matches = $this->connection->query('SELECT COUNT(*) FROM {test} WHERE [job] = :job', [':job' => 'Musician'])->fetchField();
    $this->assertSame('1', $num_matches, 'Updated fields successfully.');
  }

  /**
   * Tests UPDATE with expression values.
   */
  public function testUpdateExpression() {
    $before_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Ringo'])->fetchField();
    $num_updated = $this->connection->update('test')
      ->condition('name', 'Ringo')
      ->fields(['job' => 'Musician'])
      ->expression('age', '[age] + :age', [':age' => 4])
      ->execute();
    $this->assertSame(1, $num_updated, 'Updated 1 record.');

    $num_matches = $this->connection->query('SELECT COUNT(*) FROM {test} WHERE [job] = :job', [':job' => 'Musician'])->fetchField();
    $this->assertSame('1', $num_matches, 'Updated fields successfully.');

    $person = $this->connection->query('SELECT * FROM {test} WHERE [name] = :name', [':name' => 'Ringo'])->fetch();
    $this->assertEquals('Ringo', $person->name, 'Name set correctly.');
    $this->assertEquals($before_age + 4, $person->age, 'Age set correctly.');
    $this->assertEquals('Musician', $person->job, 'Job set correctly.');
  }

  /**
   * Tests UPDATE with only expression values.
   */
  public function testUpdateOnlyExpression() {
    $before_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Ringo'])->fetchField();
    $num_updated = $this->connection->update('test')
      ->condition('name', 'Ringo')
      ->expression('age', '[age] + :age', [':age' => 4])
      ->execute();
    $this->assertSame(1, $num_updated, 'Updated 1 record.');

    $after_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Ringo'])->fetchField();
    $this->assertEquals($before_age + 4, $after_age, 'Age updated correctly');
  }

  /**
   * Tests UPDATE with a subselect value.
   */
  public function testSubSelectUpdate() {
    $subselect = $this->connection->select('test_task', 't');
    $subselect->addExpression('MAX([priority]) + :increment', 'max_priority', [':increment' => 30]);
    // Clone this to make sure we are running a different query when
    // asserting.
    $select = clone $subselect;
    $query = $this->connection->update('test')
      ->expression('age', $subselect)
      ->condition('name', 'Ringo');
    // Save the number of rows that updated for assertion later.
    $num_updated = $query->execute();
    $after_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Ringo'])->fetchField();
    $expected_age = $select->execute()->fetchField();
    $this->assertEquals($expected_age, $after_age);
    $this->assertEquals(1, $num_updated, t('Expected 1 row to be updated in subselect update query.'));
  }

}

<?php

namespace Drupal\KernelTests\Core\Database;

/**
 * Tests queries that include reserved words.
 *
 * @group Database
 */
class ReservedWordTest extends DatabaseTestBase {

  /**
   * Tests SELECT count query from a table with a reserved name.
   */
  public function testSelectReservedWordTableCount() {
    $query = $this->connection->select('virtual');
    $num_records = $query->countQuery()->execute()->fetchField();

    $this->assertSame('1', $num_records);
  }

  /**
   * Tests SELECT query with a specific field from a table with a reserved name.
   */
  public function testSelectReservedWordTableSpecificField() {
    $query = $this->connection->select('virtual');
    $query->addField('virtual', 'function');
    $rows = $query->execute()->fetchCol();

    $this->assertSame('Function value 1', $rows[0]);
  }

  /**
   * Tests SELECT query with all fields from a table with a reserved name.
   */
  public function testSelectReservedWordTableAllFields() {
    $query = $this->connection->select('virtual');
    $query->fields('virtual');
    $result = $query->execute()->fetchObject();

    $this->assertSame('Function value 1', $result->function);
  }

  /**
   * Tests SELECT count query from a table with a reserved alias.
   */
  public function testSelectReservedWordAliasCount() {
    $query = $this->connection->select('test', 'character');
    $num_records = $query->countQuery()->execute()->fetchField();

    $this->assertSame('4', $num_records);
  }

  /**
   * Tests SELECT query with specific fields from a table with a reserved alias.
   */
  public function testSelectReservedWordAliasSpecificFields() {
    $query = $this->connection->select('test', 'high_priority');
    $query->addField('high_priority', 'name');
    $query->addField('high_priority', 'age', 'age');
    $query->condition('age', 27);
    $record = $query->execute()->fetchObject();

    // Ensure that we got the right record.
    $this->assertSame('George', $record->name);
    $this->assertSame('27', $record->age);
  }

  /**
   * Tests SELECT query with all fields from a table with a reserved alias.
   */
  public function testSelectReservedWordAliasAllFields() {
    $record = $this->connection->select('test', 'signal')
      ->fields('signal')
      ->condition('age', 27)
      ->execute()->fetchObject();

    // Ensure that we got the right record.
    $this->assertSame('George', $record->name);
    $this->assertSame('27', $record->age);
  }

  /**
   * Tests SELECT query with GROUP BY clauses on fields with reserved names.
   */
  public function testGroupBy() {
    $this->connection->insert('select')
      ->fields([
        'id' => 2,
        'update' => 'Update value 1',
      ])
      ->execute();

    // Using aliases.
    $query = $this->connection->select('select', 's');
    $query->addExpression('COUNT([id])', 'num');
    $query->addField('s', 'update');
    $query->groupBy('s.update');
    $this->assertSame('2', $query->execute()->fetchAssoc()['num']);

    // Not using aliases.
    $query = $this->connection->select('select');
    $query->addExpression('COUNT([id])', 'num');
    $query->addField('select', 'update');
    $query->groupBy('update');
    $this->assertSame('2', $query->execute()->fetchAssoc()['num']);
  }

}

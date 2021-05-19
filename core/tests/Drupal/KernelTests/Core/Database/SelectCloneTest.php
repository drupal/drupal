<?php

namespace Drupal\KernelTests\Core\Database;

/**
 * Tests cloning Select queries.
 *
 * @group Database
 */
class SelectCloneTest extends DatabaseTestBase {

  /**
   * Test that subqueries as value within conditions are cloned properly.
   */
  public function testSelectConditionSubQueryCloning() {
    $subquery = $this->connection->select('test', 't');
    $subquery->addField('t', 'id', 'id');
    $subquery->condition('age', 28, '<');

    $query = $this->connection->select('test', 't');
    $query->addField('t', 'name', 'name');
    $query->condition('id', $subquery, 'IN');

    $clone = clone $query;

    // Cloned query should have a different unique identifier.
    $this->assertNotEquals($query->uniqueIdentifier(), $clone->uniqueIdentifier());

    // Cloned query should not be altered by the following modification
    // happening on original query.
    $subquery->condition('age', 25, '>');

    $clone_result = $clone->countQuery()->execute()->fetchField();
    $query_result = $query->countQuery()->execute()->fetchField();

    // Make sure the cloned query has not been modified
    $this->assertEquals(3, $clone_result, 'The cloned query returns the expected number of rows');
    $this->assertEquals(2, $query_result, 'The query returns the expected number of rows');
  }

  /**
   * Tests that nested SELECT queries are cloned properly.
   */
  public function testNestedQueryCloning() {
    $sub_query = $this->connection->select('test', 't');
    $sub_query->addField('t', 'id', 'id');
    $sub_query->condition('age', 28, '<');

    $query = $this->connection->select($sub_query, 't');

    $clone = clone $query;

    // Cloned query should have a different unique identifier.
    $this->assertNotEquals($query->uniqueIdentifier(), $clone->uniqueIdentifier());

    // Cloned query should not be altered by the following modification
    // happening on original query.
    $sub_query->condition('age', 25, '>');

    $clone_result = $clone->countQuery()->execute()->fetchField();
    $query_result = $query->countQuery()->execute()->fetchField();

    // Make sure the cloned query has not been modified.
    $this->assertEquals(3, $clone_result, 'The cloned query returns the expected number of rows');
    $this->assertEquals(2, $query_result, 'The query returns the expected number of rows');
  }

}

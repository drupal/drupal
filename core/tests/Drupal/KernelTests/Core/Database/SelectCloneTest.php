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
    $subquery = db_select('test', 't');
    $subquery->addField('t', 'id', 'id');
    $subquery->condition('age', 28, '<');

    $query = db_select('test', 't');
    $query->addField('t', 'name', 'name');
    $query->condition('id', $subquery, 'IN');

    $clone = clone $query;
    // Cloned query should not be altered by the following modification
    // happening on original query.
    $subquery->condition('age', 25, '>');

    $clone_result = $clone->countQuery()->execute()->fetchField();
    $query_result = $query->countQuery()->execute()->fetchField();

    // Make sure the cloned query has not been modified
    $this->assertEqual(3, $clone_result, 'The cloned query returns the expected number of rows');
    $this->assertEqual(2, $query_result, 'The query returns the expected number of rows');
  }

}

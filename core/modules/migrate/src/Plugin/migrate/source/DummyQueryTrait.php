<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\source\DummyQueryTrait.
 */

namespace Drupal\migrate\Plugin\migrate\source;

/**
 * Trait providing a dummy select query object for source plugins based on
 * SqlBase which override initializeIterator() to obtain their data from other
 * SqlBase services instead of a direct query. This ensures that query() returns
 * a valid object, even though it isn't used for iteration.
 */
trait DummyQueryTrait {

  /**
   * @return \Drupal\Core\Database\Query\SelectInterface
   */
  public function query() {
    // Pass an arbritrary table name - the query should never be executed anyway.
    $query = $this->select(uniqid(), 's')
      ->range(0, 1);
    $query->addExpression('1');
    return $query;
  }

}

<?php

namespace Drupal\migrate\Plugin\migrate\source;

/**
 * Provides a dummy select query object for source plugins.
 *
 * Trait providing a dummy select query object for source plugins based on
 * SqlBase which override initializeIterator() to obtain their data from other
 * SqlBase services instead of a direct query. This ensures that query() returns
 * a valid object, even though it is not used for iteration.
 */
trait DummyQueryTrait {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Pass an arbitrary table name - the query should never be executed
    // anyway.
    $query = $this->select(uniqid(), 's')
      ->range(0, 1);
    $query->addExpression('1');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function doCount() {
    return 1;
  }

}

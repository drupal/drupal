<?php

namespace Drupal\Core\Database\Driver\mysql;

use Drupal\Core\Database\Query\Merge as QueryMerge;

/**
 * MySQL implementation of \Drupal\Core\Database\Query\Merge.
 */
class Merge extends QueryMerge {

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $connection, string $table, array $options = []) {
    // @todo Remove the __construct in D10.
    // @see https://www.drupal.org/project/drupal/issues/3210310
    parent::__construct($connection, $table, $options);
    unset($this->queryOptions['return']);
  }

}

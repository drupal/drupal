<?php

namespace Drupal\Core\Database\Driver\pgsql;

use Drupal\Core\Database\Query\Merge as QueryMerge;

/**
 * PostgreSQL implementation of \Drupal\Core\Database\Query\Merge.
 */
class Merge extends QueryMerge {

  /**
   * {@inheritdoc}
   */
  public function __construct($connection, $table, array $options = []) {
    // @todo Remove the __construct in D10.
    // @see https://www.drupal.org/project/drupal/issues/3210310
    parent::__construct($connection, $table, $options);
    unset($options['return']);
  }

}

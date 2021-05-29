<?php

namespace Drupal\Core\Database\Driver\mysql;

use Drupal\Core\Database\Query\Update as QueryUpdate;

/**
 * MySQL implementation of \Drupal\Core\Database\Query\Update.
 */
class Update extends QueryUpdate {

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

<?php

namespace Drupal\workspaces\EntityQuery;

use Drupal\Core\Entity\Query\Sql\QueryAggregate as BaseQueryAggregate;

/**
 * Alters aggregate entity queries to use a workspace revision if possible.
 */
class QueryAggregate extends BaseQueryAggregate {

  use QueryTrait {
    prepare as traitPrepare;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare() {
    // Aggregate entity queries do not return an array of entity IDs keyed by
    // revision IDs, they only return the values of the aggregated fields, so we
    // don't need to add any expressions like we do in
    // \Drupal\workspaces\EntityQuery\Query::prepare().
    $this->traitPrepare();

    // Throw away the ID fields.
    $this->sqlFields = [];
    return $this;
  }

}

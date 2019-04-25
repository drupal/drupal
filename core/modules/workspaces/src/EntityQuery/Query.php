<?php

namespace Drupal\workspaces\EntityQuery;

use Drupal\Core\Entity\Query\Sql\Query as BaseQuery;

/**
 * Alters entity queries to use a workspace revision instead of the default one.
 */
class Query extends BaseQuery {

  use QueryTrait {
    prepare as traitPrepare;
  }

  /**
   * Stores the SQL expressions used to build the SQL query.
   *
   * The array is keyed by the expression alias and the values are the actual
   * expressions.
   *
   * @var array
   *   An array of expressions.
   */
  protected $sqlExpressions = [];

  /**
   * {@inheritdoc}
   */
  public function prepare() {
    $this->traitPrepare();

    // If the prepare() method from the trait decided that we need to alter this
    // query, we need to re-define the the key fields for fetchAllKeyed() as SQL
    // expressions.
    if ($this->sqlQuery->getMetaData('active_workspace_id')) {
      $id_field = $this->entityType->getKey('id');
      $revision_field = $this->entityType->getKey('revision');

      // Since the query is against the base table, we have to take into account
      // that the revision ID might come from the workspace_association
      // relationship, and, as a consequence, the revision ID field is no longer
      // a simple SQL field but an expression.
      $this->sqlFields = [];
      $this->sqlExpressions[$revision_field] = "COALESCE(workspace_association.target_entity_revision_id, base_table.$revision_field)";
      $this->sqlExpressions[$id_field] = "base_table.$id_field";
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function finish() {
    foreach ($this->sqlExpressions as $alias => $expression) {
      $this->sqlQuery->addExpression($expression, $alias);
    }
    return parent::finish();
  }

}

<?php

namespace Drupal\workspaces\EntityQuery;

use Drupal\Core\Entity\Query\Sql\Query as BaseQuery;

/**
 * Alters entity queries to use a workspace revision instead of the default one.
 *
 * Code that needs to bypass this behavior and get the un-altered result of an
 * entity query can either be executed outside of a workspace context:
 * @code
 * \Drupal::service('workspaces.manager')->executeOutsideWorkspace(function () {
 *   // Execute an entity query that will return default revisions.
 * });
 * @endcode
 * or pass 'skip_workspaces_alter' with a value of TRUE as query metadata:
 * @code
 * \Drupal::entityTypeManager()->getStorage('<entity_type_id>')->getQuery()
 *   ->addMetaData('skip_workspaces_alter', TRUE)
 *   ->execute;
 * @endcode
 */
class Query extends BaseQuery {

  use QueryTrait {
    prepare as traitPrepare;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare() {
    $this->traitPrepare();

    // If the prepare() method from the trait decided that we need to alter this
    // query, we need to re-define the key fields for fetchAllKeyed() as SQL
    // expressions.
    if ($this->sqlQuery->getMetaData('active_workspace_id')) {
      $id_field = $this->entityType->getKey('id');
      $revision_field = $this->entityType->getKey('revision');

      // Since the query is against the base table, we have to take into account
      // that the revision ID might come from the workspace_association
      // relationship, and, as a consequence, the revision ID field is no longer
      // a simple SQL field but an expression.
      $this->sqlFields = [];
      $this->sqlQuery->addExpression("COALESCE([workspace_association].[target_entity_revision_id], [base_table].[$revision_field])", $revision_field);
      $this->sqlQuery->addExpression("[base_table].[$id_field]", $id_field);

      $this->sqlGroupBy['workspace_association.target_entity_revision_id'] = 'workspace_association.target_entity_revision_id';
      $this->sqlGroupBy["base_table.$id_field"] = "base_table.$id_field";
      $this->sqlGroupBy["base_table.$revision_field"] = "base_table.$revision_field";
    }

    return $this;
  }

}

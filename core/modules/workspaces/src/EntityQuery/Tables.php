<?php

namespace Drupal\workspaces\EntityQuery;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\Query\Sql\Tables as BaseTables;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Alters entity queries to use a workspace revision instead of the default one.
 */
class Tables extends BaseTables {

  /**
   * The workspace manager.
   *
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * Workspace association table array, key is base table name, value is alias.
   *
   * @var array
   */
  protected $contentWorkspaceTables = [];

  /**
   * Keeps track of the entity type IDs for each base table of the query.
   *
   * The array is keyed by the base table alias and the values are entity type
   * IDs.
   *
   * @var array
   */
  protected $baseTablesEntityType = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(SelectInterface $sql_query) {
    parent::__construct($sql_query);

    $this->workspaceManager = \Drupal::service('workspaces.manager');

    // The join between the first 'workspace_association' table and base table
    // of the query is done in
    // \Drupal\workspaces\EntityQuery\QueryTrait::prepare(), so we need to
    // initialize its entry manually.
    if ($this->sqlQuery->getMetaData('active_workspace_id')) {
      $this->contentWorkspaceTables['base_table'] = 'workspace_association';
      $this->baseTablesEntityType['base_table'] = $this->sqlQuery->getMetaData('entity_type');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addField($field, $type, $langcode) {
    // The parent method uses shared and dedicated revision tables only when the
    // entity query is instructed to query all revisions. However, if we are
    // looking for workspace-specific revisions, we have to force the parent
    // method to always pick the revision tables if the field being queried is
    // revisionable.
    if ($this->sqlQuery->getMetaData('active_workspace_id')) {
      $previous_all_revisions = $this->sqlQuery->getMetaData('all_revisions');
      $this->sqlQuery->addMetaData('all_revisions', TRUE);
    }

    $alias = parent::addField($field, $type, $langcode);

    // Restore the 'all_revisions' metadata because we don't want to interfere
    // with the rest of the query.
    if (isset($previous_all_revisions)) {
      $this->sqlQuery->addMetaData('all_revisions', $previous_all_revisions);
    }

    return $alias;
  }

  /**
   * {@inheritdoc}
   */
  protected function addJoin($type, $table, $join_condition, $langcode, $delta = NULL) {
    if ($this->sqlQuery->getMetaData('active_workspace_id')) {
      // The join condition for a shared or dedicated field table is in the form
      // of "%alias.$id_field = $base_table.$id_field". Whenever we join a field
      // table we have to check:
      // 1) if $base_table is of an entity type that can belong to a workspace;
      // 2) if $id_field is the revision key of that entity type or the special
      // 'revision_id' string used when joining dedicated field tables.
      // If those two conditions are met, we have to update the join condition
      // to also look for a possible workspace-specific revision using COALESCE.
      $condition_parts = explode(' = ', $join_condition);
      $condition_parts_1 = str_replace(['[', ']'], '', $condition_parts[1]);
      list($base_table, $id_field) = explode('.', $condition_parts_1);

      if (isset($this->baseTablesEntityType[$base_table])) {
        $entity_type_id = $this->baseTablesEntityType[$base_table];
        $revision_key = $this->entityTypeManager->getActiveDefinition($entity_type_id)->getKey('revision');

        if ($id_field === $revision_key || $id_field === 'revision_id') {
          $workspace_association_table = $this->contentWorkspaceTables[$base_table];
          $join_condition = "{$condition_parts[0]} = COALESCE($workspace_association_table.target_entity_revision_id, {$condition_parts[1]})";
        }
      }
    }

    return parent::addJoin($type, $table, $join_condition, $langcode, $delta);
  }

  /**
   * {@inheritdoc}
   */
  protected function addNextBaseTable(EntityType $entity_type, $table, $sql_column, FieldStorageDefinitionInterface $field_storage) {
    $next_base_table_alias = parent::addNextBaseTable($entity_type, $table, $sql_column, $field_storage);

    $active_workspace_id = $this->sqlQuery->getMetaData('active_workspace_id');
    if ($active_workspace_id && $this->workspaceManager->isEntityTypeSupported($entity_type)) {
      $this->addWorkspaceAssociationJoin($entity_type->id(), $next_base_table_alias, $active_workspace_id);
    }

    return $next_base_table_alias;
  }

  /**
   * Adds a new join to the 'workspace_association' table for an entity base table.
   *
   * This method assumes that the active workspace has already been determined
   * to be a non-default workspace.
   *
   * @param string $entity_type_id
   *   The ID of the entity type whose base table we are joining.
   * @param string $base_table_alias
   *   The alias of the entity type's base table.
   * @param string $active_workspace_id
   *   The ID of the active workspace.
   *
   * @return string
   *   The alias of the joined table.
   */
  public function addWorkspaceAssociationJoin($entity_type_id, $base_table_alias, $active_workspace_id) {
    if (!isset($this->contentWorkspaceTables[$base_table_alias])) {
      $entity_type = $this->entityTypeManager->getActiveDefinition($entity_type_id);
      $id_field = $entity_type->getKey('id');

      // LEFT join the Workspace association entity's table so we can properly
      // include live content along with a possible workspace-specific revision.
      $this->contentWorkspaceTables[$base_table_alias] = $this->sqlQuery->leftJoin('workspace_association', NULL, "[%alias].[target_entity_type_id] = '$entity_type_id' AND [%alias].[target_entity_id] = [$base_table_alias].[$id_field] AND [%alias].[workspace] = '$active_workspace_id'");

      $this->baseTablesEntityType[$base_table_alias] = $entity_type->id();
    }
    return $this->contentWorkspaceTables[$base_table_alias];
  }

}

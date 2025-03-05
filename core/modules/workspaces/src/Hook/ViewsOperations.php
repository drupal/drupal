<?php

declare(strict_types=1);

namespace Drupal\workspaces\Hook;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\views\Plugin\ViewsHandlerManager;
use Drupal\views\Plugin\views\join\JoinPluginInterface;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\Plugin\views\query\Sql;
use Drupal\views\ViewExecutable;
use Drupal\views\ViewsData;
use Drupal\workspaces\WorkspaceAssociation;
use Drupal\workspaces\WorkspaceInformationInterface;
use Drupal\workspaces\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Defines a class for altering views queries.
 */
class ViewsOperations {

  /**
   * An array of tables adjusted for workspace_association join.
   *
   * @var \WeakMap
   */
  private \WeakMap $adjustedTables;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected WorkspaceManagerInterface $workspaceManager,
    protected WorkspaceInformationInterface $workspaceInfo,
    protected LanguageManagerInterface $languageManager,
    protected ?ViewsData $viewsData = NULL,
    #[Autowire(service: 'plugin.manager.views.join')]
    protected ?ViewsHandlerManager $viewsJoinPluginManager = NULL,
  ) {
    $this->adjustedTables = new \WeakMap();
  }

  /**
   * Implements hook_views_query_alter().
   */
  #[Hook('views_query_alter')]
  public function viewsQueryAlter(ViewExecutable $view, QueryPluginBase $query): void {
    // Don't alter any views queries if we're not in a workspace context.
    if (!$this->workspaceManager->hasActiveWorkspace()) {
      return;
    }

    // Don't alter any non-sql views queries.
    if (!$query instanceof Sql) {
      return;
    }

    // Find out what entity types are represented in this query.
    $entity_type_ids = [];
    foreach ($query->relationships as $info) {
      $table_data = $this->viewsData->get($info['base']);
      if (empty($table_data['table']['entity type'])) {
        continue;
      }
      $entity_type_id = $table_data['table']['entity type'];
      // This construct ensures each entity type exists only once.
      $entity_type_ids[$entity_type_id] = $entity_type_id;
    }

    $entity_type_definitions = $this->entityTypeManager->getDefinitions();
    foreach ($entity_type_ids as $entity_type_id) {
      if ($this->workspaceInfo->isEntityTypeSupported($entity_type_definitions[$entity_type_id])) {
        $this->alterQueryForEntityType($query, $entity_type_definitions[$entity_type_id]);
      }
    }
  }

  /**
   * Alters the entity type tables for a Views query.
   *
   * This should only be called after determining that this entity type is
   * involved in the query, and that a non-default workspace is in use.
   *
   * @param \Drupal\views\Plugin\views\query\Sql $query
   *   The query plugin object for the query.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   */
  protected function alterQueryForEntityType(Sql $query, EntityTypeInterface $entity_type): void {
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $this->entityTypeManager->getStorage($entity_type->id())->getTableMapping();
    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type->id());
    $dedicated_field_storage_definitions = array_filter($field_storage_definitions, function ($definition) use ($table_mapping) {
      return $table_mapping->requiresDedicatedTableStorage($definition);
    });
    $dedicated_field_data_tables = array_map(function ($definition) use ($table_mapping) {
      return $table_mapping->getDedicatedDataTableName($definition);
    }, $dedicated_field_storage_definitions);

    $move_workspace_tables = [];
    $table_queue =& $query->getTableQueue();
    foreach ($table_queue as $alias => &$table_info) {
      // If we reach the workspace_association array item before any candidates,
      // then we do not need to move it.
      if ($table_info['table'] == 'workspace_association') {
        break;
      }

      // Any dedicated field table is a candidate.
      if ($field_name = array_search($table_info['table'], $dedicated_field_data_tables, TRUE)) {
        $relationship = $table_info['relationship'];

        // There can be reverse relationships used. If so, Workspaces can't do
        // anything with them. Detect this and skip.
        if ($table_info['join']->field != 'entity_id') {
          continue;
        }

        // Get the dedicated revision table name.
        $new_table_name = $table_mapping->getDedicatedRevisionTableName($field_storage_definitions[$field_name]);

        // Now add the workspace_association table.
        $workspace_association_table = $this->ensureWorkspaceAssociationTable($entity_type->id(), $query, $relationship);

        // Update the join to use our COALESCE.
        $revision_field = $entity_type->getKey('revision');
        $table_info['join']->leftFormula = "COALESCE($workspace_association_table.target_entity_revision_id, $relationship.$revision_field)";

        // Update the join and the table info to our new table name, and to join
        // on the revision key.
        $table_info['table'] = $new_table_name;
        $table_info['join']->table = $new_table_name;
        $table_info['join']->field = 'revision_id';

        // Finally, if we added the workspace_association table we have to move
        // it in the table queue so that it comes before this field.
        if (empty($move_workspace_tables[$workspace_association_table])) {
          $move_workspace_tables[$workspace_association_table] = $alias;
        }
      }
    }

    // JOINs must be in order. i.e, any tables you mention in the ON clause of a
    // JOIN must appear prior to that JOIN. Since we're modifying a JOIN in
    // place, and adding a new table, we must ensure that the new table appears
    // prior to this one. So we recorded at what index we saw that table, and
    // then use array_splice() to move the workspace_association table join to
    // the correct position.
    foreach ($move_workspace_tables as $workspace_association_table => $alias) {
      $this->moveEntityTable($query, $workspace_association_table, $alias);
    }

    $base_entity_table = $entity_type->isTranslatable() ? $entity_type->getDataTable() : $entity_type->getBaseTable();

    $base_fields = array_diff($table_mapping->getFieldNames($entity_type->getBaseTable()), [$entity_type->getKey('langcode')]);
    $revisionable_fields = array_diff($table_mapping->getFieldNames($entity_type->getRevisionDataTable()), $base_fields);

    // Go through and look to see if we have to modify fields and filters.
    foreach ($query->fields as &$field_info) {
      // Some fields don't actually have tables, meaning they're formulae and
      // whatnot. At this time we are going to ignore those.
      if (empty($field_info['table'])) {
        continue;
      }

      // Dereference the alias into the actual table.
      $table = $table_queue[$field_info['table']]['table'];
      if ($table == $base_entity_table && in_array($field_info['field'], $revisionable_fields)) {
        $relationship = $table_queue[$field_info['table']]['alias'];
        $alias = $this->ensureRevisionTable($entity_type, $query, $relationship);
        if ($alias) {
          // Change the base table to use the revision table instead.
          $field_info['table'] = $alias;
        }
      }
    }

    $relationships = [];
    // Build a list of all relationships that might be for our table.
    foreach ($query->relationships as $relationship => $info) {
      if ($info['base'] == $base_entity_table) {
        $relationships[] = $relationship;
      }
    }

    // Now we have to go through our where clauses and modify any of our fields.
    foreach ($query->where as &$clauses) {
      foreach ($clauses['conditions'] as &$where_info) {
        // Build a matrix of our possible relationships against fields we need
        // to switch.
        foreach ($relationships as $relationship) {
          foreach ($revisionable_fields as $field) {
            if (is_string($where_info['field']) && $where_info['field'] == "$relationship.$field") {
              $alias = $this->ensureRevisionTable($entity_type, $query, $relationship);
              if ($alias) {
                // Change the base table to use the revision table instead.
                $where_info['field'] = "$alias.$field";
              }
            }
          }
        }
      }
    }

    // @todo Handle $query->orderby, $query->groupBy, $query->having and
    //   $query->count_field in https://www.drupal.org/node/2968165.
  }

  /**
   * Adds the 'workspace_association' table to a views query.
   *
   * @param string $entity_type_id
   *   The ID of the entity type to join.
   * @param \Drupal\views\Plugin\views\query\Sql $query
   *   The query plugin object for the query.
   * @param string $relationship
   *   The primary table alias this table is related to.
   *
   * @return string
   *   The alias of the 'workspace_association' table.
   */
  protected function ensureWorkspaceAssociationTable(string $entity_type_id, Sql $query, string $relationship): string {
    if (isset($query->tables[$relationship]['workspace_association'])) {
      return $query->tables[$relationship]['workspace_association']['alias'];
    }

    $table_data = $this->viewsData->get($query->relationships[$relationship]['base']);

    // Construct the join.
    $definition = [
      'table' => 'workspace_association',
      'field' => WorkspaceAssociation::getIdField($entity_type_id),
      'left_table' => $relationship,
      'left_field' => $table_data['table']['base']['field'],
      'extra' => [
        [
          'field' => 'target_entity_type_id',
          'value' => $entity_type_id,
        ],
        [
          'field' => 'workspace',
          'value' => $this->workspaceManager->getActiveWorkspace()->id(),
        ],
      ],
      'type' => 'LEFT',
    ];

    $join = $this->viewsJoinPluginManager->createInstance('standard', $definition);
    $join->adjusted = TRUE;

    return $query->queueTable('workspace_association', $relationship, $join);
  }

  /**
   * Adds the revision table of an entity type to a query object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\views\Plugin\views\query\Sql $query
   *   The query plugin object for the query.
   * @param string $relationship
   *   The name of the relationship.
   *
   * @return string
   *   The alias of the relationship.
   */
  protected function ensureRevisionTable(EntityTypeInterface $entity_type, Sql $query, string $relationship): string {
    // Get the alias for the 'workspace_association' table we chain off of in
    // the COALESCE.
    $workspace_association_table = $this->ensureWorkspaceAssociationTable($entity_type->id(), $query, $relationship);

    // Get the name of the revision table and revision key.
    $base_revision_table = $entity_type->isTranslatable() ? $entity_type->getRevisionDataTable() : $entity_type->getRevisionTable();
    $revision_field = $entity_type->getKey('revision');

    // If the table was already added and has a join against the same field on
    // the revision table, reuse that rather than adding a new join.
    if (isset($query->tables[$relationship][$base_revision_table])) {
      $table_queue =& $query->getTableQueue();
      $alias = $query->tables[$relationship][$base_revision_table]['alias'];
      if (isset($table_queue[$alias]['join']->field) && $table_queue[$alias]['join']->field == $revision_field) {
        // If this table previously existed, but was not added by us, we need
        // to modify the join and make sure that 'workspace_association' comes
        // first.
        if (!$this->adjustedTables->offsetExists($table_queue[$alias]['join'])) {
          $table_queue[$alias]['join'] = $this->getRevisionTableJoin($relationship, $base_revision_table, $revision_field, $workspace_association_table, $entity_type);
          // We also have to ensure that our 'workspace_association' comes
          // before this.
          $this->moveEntityTable($query, $workspace_association_table, $alias);
        }

        return $alias;
      }
    }

    // Construct a new join.
    $join = $this->getRevisionTableJoin($relationship, $base_revision_table, $revision_field, $workspace_association_table, $entity_type);
    return $query->queueTable($base_revision_table, $relationship, $join);
  }

  /**
   * Fetches a join for a revision table using the workspace_association table.
   *
   * @param string $relationship
   *   The relationship to use in the view.
   * @param string $table
   *   The table name.
   * @param string $field
   *   The field to join on.
   * @param string $workspace_association_table
   *   The alias of the 'workspace_association' table joined to the main entity
   *   table.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type that is being queried.
   *
   * @return \Drupal\views\Plugin\views\join\JoinPluginInterface
   *   An adjusted views join object to add to the query.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getRevisionTableJoin(string $relationship, string $table, string $field, string $workspace_association_table, EntityTypeInterface $entity_type): JoinPluginInterface {
    $definition = [
      'table' => $table,
      'field' => $field,
      'left_table' => $relationship,
      'left_formula' => "COALESCE($workspace_association_table.target_entity_revision_id, $relationship.$field)",
    ];

    if ($entity_type->isTranslatable() && $this->languageManager->isMultilingual()) {
      $langcode_field = $entity_type->getKey('langcode');
      $definition['extra'] = [
        ['field' => $langcode_field, 'left_field' => $langcode_field],
      ];
    }

    /** @var \Drupal\views\Plugin\views\join\JoinPluginInterface $join */
    $join = $this->viewsJoinPluginManager->createInstance('standard', $definition);
    $join->adjusted = TRUE;
    $this->adjustedTables[$join] = TRUE;

    return $join;
  }

  /**
   * Moves a 'workspace_association' table to appear before the given alias.
   *
   * Because Workspace chains possibly pre-existing tables onto the
   * 'workspace_association' table, we have to ensure that the
   * 'workspace_association' table appears in the query before the alias it's
   * chained on or the SQL is invalid.
   *
   * @param \Drupal\views\Plugin\views\query\Sql $query
   *   The SQL query object.
   * @param string $workspace_association_table
   *   The alias of the 'workspace_association' table.
   * @param string $alias
   *   The alias of the table it needs to appear before.
   */
  protected function moveEntityTable(Sql $query, string $workspace_association_table, string $alias): void {
    $table_queue =& $query->getTableQueue();
    $keys = array_keys($table_queue);
    $current_index = array_search($workspace_association_table, $keys);
    $index = array_search($alias, $keys);

    // If it's already before our table, we don't need to move it, as we could
    // accidentally move it forward.
    if ($current_index < $index) {
      return;
    }
    $splice = [$workspace_association_table => $table_queue[$workspace_association_table]];
    unset($table_queue[$workspace_association_table]);

    // Now move the item to the proper location in the array. Don't use
    // array_splice() because that breaks indices.
    $table_queue = array_slice($table_queue, 0, $index, TRUE) +
      $splice +
      array_slice($table_queue, $index, NULL, TRUE);
  }

}

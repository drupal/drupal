<?php

namespace Drupal\workspaces\EntityQuery;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\workspaces\WorkspaceManagerInterface;

/**
 * Provides workspaces-specific helpers for altering entity queries.
 */
trait QueryTrait {

  /**
   * The workspace manager.
   *
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * Constructs a Query object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param string $conjunction
   *   - AND: all of the conditions on the query need to match.
   *   - OR: at least one of the conditions on the query need to match.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to run the query against.
   * @param array $namespaces
   *   List of potential namespaces of the classes belonging to this query.
   * @param \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager.
   */
  public function __construct(EntityTypeInterface $entity_type, $conjunction, Connection $connection, array $namespaces, WorkspaceManagerInterface $workspace_manager) {
    parent::__construct($entity_type, $conjunction, $connection, $namespaces);

    $this->workspaceManager = $workspace_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare() {
    parent::prepare();

    // Do not alter entity revision queries.
    // @todo How about queries for the latest revision? Should we alter them to
    //   look for the latest workspace-specific revision?
    if ($this->allRevisions) {
      return $this;
    }

    // Only alter the query if the active workspace is not the default one and
    // the entity type is supported.
    if ($this->workspaceManager->hasActiveWorkspace() && $this->workspaceManager->isEntityTypeSupported($this->entityType)) {
      $active_workspace = $this->workspaceManager->getActiveWorkspace();
      $this->sqlQuery->addMetaData('active_workspace_id', $active_workspace->id());
      $this->sqlQuery->addMetaData('simple_query', FALSE);

      // LEFT JOIN 'workspace_association' to the base table of the query so we
      // can properly include live content along with a possible workspace
      // revision.
      $id_field = $this->entityType->getKey('id');
      $this->sqlQuery->leftJoin('workspace_association', 'workspace_association', "[%alias].[target_entity_type_id] = '{$this->entityTypeId}' AND [%alias].[target_entity_id] = [base_table].[$id_field] AND [%alias].[workspace] = '{$active_workspace->id()}'");
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isSimpleQuery() {
    // We declare that this is not a simple query in
    // \Drupal\workspaces\EntityQuery\QueryTrait::prepare(), but that's not
    // enough because the parent method can return TRUE in some circumstances.
    if ($this->sqlQuery->getMetaData('active_workspace_id')) {
      return FALSE;
    }

    return parent::isSimpleQuery();
  }

}

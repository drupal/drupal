<?php

namespace Drupal\workspaces;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Path\AliasStorage as CoreAliasStorage;

/**
 * Provides workspace-specific path alias lookup queries.
 */
class AliasStorage extends CoreAliasStorage {

  /**
   * The workspace manager.
   *
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * AliasStorage constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection for reading and writing path aliases.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager service.
   */
  public function __construct(Connection $connection, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, WorkspaceManagerInterface $workspace_manager) {
    parent::__construct($connection, $module_handler, $entity_type_manager);
    $this->workspaceManager = $workspace_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseQuery() {
    // Don't alter any queries if we're not in a workspace context.
    if (!$this->workspaceManager->hasActiveWorkspace()) {
      return parent::getBaseQuery();
    }

    $active_workspace = $this->workspaceManager->getActiveWorkspace();

    $query = $this->connection->select('path_alias', 'base_table_2');
    $wa_join = $query->leftJoin('workspace_association', NULL, "%alias.target_entity_type_id = 'path_alias' AND %alias.target_entity_id = base_table_2.id AND %alias.workspace = :active_workspace_id", [
      ':active_workspace_id' => $active_workspace->id(),
    ]);
    $query->innerJoin('path_alias_revision', 'base_table', "%alias.revision_id = COALESCE($wa_join.target_entity_revision_id, base_table_2.revision_id)");

    return $query;
  }

}

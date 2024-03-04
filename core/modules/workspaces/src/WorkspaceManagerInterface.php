<?php

namespace Drupal\workspaces;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides an interface for managing Workspaces.
 */
interface WorkspaceManagerInterface {

  /**
   * Returns whether an entity type can belong to a workspace or not.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to check.
   *
   * @return bool
   *   TRUE if the entity type can belong to a workspace, FALSE otherwise.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
   *   \Drupal\workspaces\WorkspaceInformation::isEntityTypeSupported instead.
   *
   * @see https://www.drupal.org/node/3324297
   */
  public function isEntityTypeSupported(EntityTypeInterface $entity_type);

  /**
   * Returns an array of entity types that can belong to workspaces.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   The entity types what can belong to workspaces.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
   *   \Drupal\workspaces\WorkspaceInformation::getSupportedEntityTypes instead.
   *
   * @see https://www.drupal.org/node/3324297
   */
  public function getSupportedEntityTypes();

  /**
   * Determines whether a workspace is active in the current request.
   *
   * @return bool
   *   TRUE if a workspace is active, FALSE otherwise.
   */
  public function hasActiveWorkspace();

  /**
   * Gets the active workspace.
   *
   * @return \Drupal\workspaces\WorkspaceInterface
   *   The active workspace entity object.
   */
  public function getActiveWorkspace();

  /**
   * Sets the active workspace via the workspace negotiators.
   *
   * @param \Drupal\workspaces\WorkspaceInterface $workspace
   *   The workspace to set as active.
   *
   * @return $this
   *
   * @throws \Drupal\workspaces\WorkspaceAccessException
   *   Thrown when the current user doesn't have access to view the workspace.
   */
  public function setActiveWorkspace(WorkspaceInterface $workspace);

  /**
   * Unsets the active workspace via the workspace negotiators.
   *
   * @return $this
   */
  public function switchToLive();

  /**
   * Executes the given callback function in the context of a workspace.
   *
   * @param string $workspace_id
   *   The ID of a workspace.
   * @param callable $function
   *   The callback to be executed.
   *
   * @return mixed
   *   The callable's return value.
   */
  public function executeInWorkspace($workspace_id, callable $function);

  /**
   * Executes the given callback function without any workspace context.
   *
   * @param callable $function
   *   The callback to be executed.
   *
   * @return mixed
   *   The callable's return value.
   */
  public function executeOutsideWorkspace(callable $function);

  /**
   * Determines whether runtime entity operations should be altered.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to check.
   *
   * @return bool
   *   TRUE if the entity operations or queries should be altered in the current
   *   request, FALSE otherwise.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3324297
   */
  public function shouldAlterOperations(EntityTypeInterface $entity_type);

  /**
   * Deletes the revisions associated with deleted workspaces.
   */
  public function purgeDeletedWorkspacesBatch();

}

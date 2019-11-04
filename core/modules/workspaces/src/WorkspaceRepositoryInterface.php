<?php

namespace Drupal\workspaces;

/**
 * Provides an interface for workspace tree lookup operations.
 */
interface WorkspaceRepositoryInterface {

  /**
   * Returns an array of workspaces tree item properties, sorted in tree order.
   *
   * @return array
   *   An array of workspace tree item properties, keyed by the workspace IDs.
   *   The tree item properties are:
   *   - depth: The depth of the workspace in the tree;
   *   - ancestors: The ancestor IDs of the workspace;
   *   - descendants: The descendant IDs of the workspace.
   */
  public function loadTree();

  /**
   * Returns the descendant IDs of the passed-in workspace, including itself.
   *
   * @param string $workspace_id
   *   A workspace ID.
   *
   * @return string[]
   *   An array of descendant workspace IDs, including the passed-in one.
   */
  public function getDescendantsAndSelf($workspace_id);

  /**
   * Resets the cached workspace tree.
   *
   * @return $this
   */
  public function resetCache();

}

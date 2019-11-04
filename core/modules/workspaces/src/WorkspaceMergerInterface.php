<?php

namespace Drupal\workspaces;

/**
 * Defines an interface for the workspace merger.
 *
 * @internal
 */
interface WorkspaceMergerInterface extends WorkspaceOperationInterface {

  /**
   * Merges the contents of the source workspace into the target workspace.
   */
  public function merge();

}

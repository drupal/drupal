<?php

namespace Drupal\workspaces;

/**
 * Defines an interface for the workspace publisher.
 *
 * @internal
 */
interface WorkspacePublisherInterface extends WorkspaceOperationInterface {

  /**
   * Publishes the contents of a workspace to the default (Live) workspace.
   */
  public function publish();

}

<?php

namespace Drupal\workspace;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;

/**
 * Provides the interface for a plugin manager of repository handlers.
 */
interface RepositoryHandlerManagerInterface extends CategorizingPluginManagerInterface {

  /**
   * Creates a repository handler instance from a given workspace entity.
   *
   * @param \Drupal\workspace\WorkspaceInterface $workspace
   *   A workspace entity.
   *
   * @return \Drupal\workspace\RepositoryHandlerInterface
   *   A repository handler plugin.
   */
  public function createFromWorkspace(WorkspaceInterface $workspace);

}

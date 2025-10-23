<?php

namespace Drupal\workspaces\Event;

use Drupal\workspaces\WorkspaceInterface;
use Drupal\Component\EventDispatcher\Event;

/**
 * Defines the workspace switch event.
 */
class WorkspaceSwitchEvent extends Event {

  public function __construct(
    protected readonly ?WorkspaceInterface $workspace = NULL,
    protected readonly ?WorkspaceInterface $previousWorkspace = NULL,
  ) {}

  /**
   * Gets the new activate workspace.
   *
   * @return \Drupal\workspaces\WorkspaceInterface|null
   *   A workspace entity, or NULL if we switched into Live.
   */
  public function getWorkspace(): ?WorkspaceInterface {
    return $this->workspace;
  }

  /**
   * Gets the previous active workspace.
   *
   * @return \Drupal\workspaces\WorkspaceInterface|null
   *   A workspace entity, or NULL if we switched from Live.
   */
  public function getPreviousWorkspace(): ?WorkspaceInterface {
    return $this->previousWorkspace;
  }

}

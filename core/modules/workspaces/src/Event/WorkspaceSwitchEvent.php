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
    protected readonly bool $isTemporary = FALSE,
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

  /**
   * Whether this switch is temporary (e.g. executeInWorkspace).
   *
   * Temporary switches are automatically reverted after the callable finishes,
   * as opposed to persistent switches via setActiveWorkspace/switchToLive.
   *
   * @return bool
   *   TRUE if the switch is temporary, FALSE otherwise.
   */
  public function isTemporary(): bool {
    return $this->isTemporary;
  }

}

<?php

namespace Drupal\workspaces;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines an interface for the workspace entity type.
 */
interface WorkspaceInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Publishes the contents of this workspace to the default (Live) workspace.
   */
  public function publish();

  /**
   * Gets the workspace creation timestamp.
   *
   * @return int
   *   Creation timestamp of the workspace.
   */
  public function getCreatedTime();

  /**
   * Sets the workspace creation timestamp.
   *
   * @param int $timestamp
   *   The workspace creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

  /**
   * Determines whether the workspace has a parent.
   *
   * @return bool
   *   TRUE if the workspace has a parent, FALSE otherwise.
   */
  public function hasParent();

}

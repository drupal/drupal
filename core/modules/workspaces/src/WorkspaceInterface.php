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
   * The ID of the default workspace.
   *
   * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
   *   \Drupal\workspaces\WorkspaceManager::hasActiveWorkspace() instead.
   *
   * @see https://www.drupal.org/node/3071527
   */
  const DEFAULT_WORKSPACE = 'live';

  /**
   * Publishes the contents of this workspace to the default (Live) workspace.
   */
  public function publish();

  /**
   * Determines whether the workspace is the default one or not.
   *
   * @return bool
   *   TRUE if this workspace is the default one (e.g 'Live'), FALSE otherwise.
   *
   * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
   *   \Drupal\workspaces\WorkspaceManager::hasActiveWorkspace() instead.
   *
   * @see https://www.drupal.org/node/3071527
   */
  public function isDefaultWorkspace();

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

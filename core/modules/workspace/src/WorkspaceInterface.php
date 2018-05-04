<?php

namespace Drupal\workspace;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines an interface for the workspace entity type.
 */
interface WorkspaceInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * The ID of the default workspace.
   */
  const DEFAULT_WORKSPACE = 'live';

  /**
   * Pushes content from this workspace to the target repository.
   */
  public function push();

  /**
   * Pulls content from the target repository into this workspace.
   */
  public function pull();

  /**
   * Gets an instance of the repository handler configured for the workspace.
   *
   * @return \Drupal\workspace\RepositoryHandlerInterface
   *   A repository handler plugin object.
   */
  public function getRepositoryHandler();

  /**
   * Determines whether the workspace is the default one or not.
   *
   * @return bool
   *   TRUE if this workspace is the default one (e.g 'Live'), FALSE otherwise.
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

}

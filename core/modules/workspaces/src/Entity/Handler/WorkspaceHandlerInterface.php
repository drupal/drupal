<?php

namespace Drupal\workspaces\Entity\Handler;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines workspace operations that need to vary by entity type.
 *
 * @internal
 */
interface WorkspaceHandlerInterface {

  /**
   * Determines if an entity should be tracked in a workspace.
   *
   * At the general level, workspace support is determined for the entire entity
   * type. If an entity type is supported, there may be further decisions each
   * entity type can make to evaluate if a given entity is appropriate to be
   * tracked in a workspace.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity we may be tracking.
   *
   * @return bool
   *   TRUE if this entity should be tracked in a workspace, FALSE otherwise.
   */
  public function isEntitySupported(EntityInterface $entity): bool;

}

<?php

namespace Drupal\workspaces\Entity\Handler;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a handler for entity types that are ignored by workspaces.
 *
 * @internal
 */
class IgnoredWorkspaceHandler implements WorkspaceHandlerInterface, EntityHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function isEntitySupported(EntityInterface $entity): bool {
    return FALSE;
  }

}

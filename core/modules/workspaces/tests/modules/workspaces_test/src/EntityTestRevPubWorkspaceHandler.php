<?php

declare(strict_types=1);

namespace Drupal\workspaces_test;

use Drupal\Core\Entity\EntityInterface;
use Drupal\workspaces\Entity\Handler\DefaultWorkspaceHandler;

/**
 * Provides a custom workspace handler for testing purposes.
 */
class EntityTestRevPubWorkspaceHandler extends DefaultWorkspaceHandler {

  /**
   * {@inheritdoc}
   */
  public function isEntitySupported(EntityInterface $entity): bool {
    return $entity->bundle() !== 'ignored_bundle';
  }

}

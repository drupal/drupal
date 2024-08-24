<?php

declare(strict_types=1);

namespace Drupal\entity_test_revlog;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_test_revlog\Entity\EntityTestWithRevisionLog;

/**
 * Defines the access control handler for test entity types.
 */
class EntityTestRevlogAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    assert($entity instanceof EntityTestWithRevisionLog);

    // Access to revisions is based on labels, so access can vary by individual
    // revisions, since the 'name' field can vary by revision.
    $labels = explode(',', $entity->label());
    $labels = array_map('trim', $labels);
    if (in_array($operation, [
      'view',
      'view label',
      'view all revisions',
      'view revision',
    ], TRUE)) {
      return AccessResult::allowedIf(in_array($operation, $labels, TRUE));
    }
    elseif ($operation === 'revert') {
      return AccessResult::allowedIf(
        // Disallow reverting to latest.
        (!$entity->isDefaultRevision() && !$entity->isLatestRevision() && in_array('revert', $labels, TRUE))
      );
    }
    elseif ($operation === 'delete revision') {
      return AccessResult::allowedIf(
        // Disallow deleting latest and current revision.
        (!$entity->isLatestRevision() && in_array('delete revision', $labels, TRUE))
      );
    }

    // No opinion.
    return AccessResult::neutral();
  }

}

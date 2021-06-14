<?php

namespace Drupal\rest\Plugin\rest\resource;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @internal
 * @todo Consider making public in https://www.drupal.org/node/2300677
 */
trait EntityResourceAccessTrait {

  /**
   * Performs edit access checks for fields.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose fields edit access should be checked for.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Throws access denied when the user does not have permissions to edit a
   *   field.
   */
  protected function checkEditFieldAccess(EntityInterface $entity) {
    // Only check 'edit' permissions for fields that were actually submitted by
    // the user. Field access makes no difference between 'create' and 'update',
    // so the 'edit' operation is used here.
    foreach ($entity->_restSubmittedFields as $key => $field_name) {
      if (!$entity->get($field_name)->access('edit')) {
        throw new AccessDeniedHttpException("Access denied on creating field '$field_name'.");
      }
    }
  }

}

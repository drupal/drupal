<?php

namespace Drupal\quickedit\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Access check for in-place editing entity fields.
 */
class QuickEditEntityFieldAccessCheck implements AccessInterface, QuickEditEntityFieldAccessCheckInterface {

  /**
   * Checks Quick Edit access to the field.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity containing the field.
   * @param string $field_name
   *   The field name.
   * @param string $langcode
   *   The langcode.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @todo Use the $account argument: https://www.drupal.org/node/2266809.
   */
  public function access(EntityInterface $entity, $field_name, $langcode, AccountInterface $account) {
    if (!$this->validateRequestAttributes($entity, $field_name, $langcode)) {
      return AccessResult::forbidden();
    }

    return $this->accessEditEntityField($entity, $field_name);
  }

  /**
   * {@inheritdoc}
   */
  public function accessEditEntityField(EntityInterface $entity, $field_name) {
    return $entity->access('update', NULL, TRUE)->andIf($entity->get($field_name)->access('edit', NULL, TRUE));
  }

  /**
   * Validates request attributes.
   */
  protected function validateRequestAttributes(EntityInterface $entity, $field_name, $langcode) {
    // Validate the field name and language.
    if (!$field_name || !$entity->hasField($field_name)) {
      return FALSE;
    }
    if (!$langcode || !$entity->hasTranslation($langcode)) {
      return FALSE;
    }

    return TRUE;
  }

}

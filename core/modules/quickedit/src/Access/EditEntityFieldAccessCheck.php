<?php

/**
 * @file
 * Contains \Drupal\quickedit\Access\EditEntityFieldAccessCheck.
 */

namespace Drupal\quickedit\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Access check for editing entity fields.
 */
class EditEntityFieldAccessCheck implements AccessInterface, EditEntityFieldAccessCheckInterface {

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
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   *
   * @todo Use the $account argument: https://drupal.org/node/2266809.
   */
  public function access(EntityInterface $entity, $field_name, $langcode, AccountInterface $account) {
    if (!$this->validateRequestAttributes($entity, $field_name, $langcode)) {
      return static::KILL;
    }

    return $this->accessEditEntityField($entity, $field_name) ? static::ALLOW : static::DENY;
  }

  /**
   * {@inheritdoc}
   */
  public function accessEditEntityField(EntityInterface $entity, $field_name) {
    return $entity->access('update') && $entity->get($field_name)->access('edit');
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

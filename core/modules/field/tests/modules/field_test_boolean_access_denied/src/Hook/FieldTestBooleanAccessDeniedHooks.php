<?php

declare(strict_types=1);

namespace Drupal\field_test_boolean_access_denied\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for field_test_boolean_access_denied.
 */
class FieldTestBooleanAccessDeniedHooks {

  /**
   * Implements hook_entity_field_access().
   */
  #[Hook('entity_field_access')]
  public function entityFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL) {
    return AccessResult::forbiddenIf($field_definition->getName() === \Drupal::state()->get('field.test_boolean_field_access_field'));
  }

}

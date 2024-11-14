<?php

declare(strict_types=1);

namespace Drupal\language_entity_field_access_test\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for language_entity_field_access_test.
 */
class LanguageEntityFieldAccessTestHooks {

  /**
   * Implements hook_entity_field_access().
   */
  #[Hook('entity_field_access')]
  public function entityFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL) {
    return AccessResult::forbidden();
  }

}

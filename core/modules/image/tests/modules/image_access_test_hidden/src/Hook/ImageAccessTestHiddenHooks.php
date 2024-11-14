<?php

declare(strict_types=1);

namespace Drupal\image_access_test_hidden\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for image_access_test_hidden.
 */
class ImageAccessTestHiddenHooks {

  /**
   * Implements hook_entity_field_access().
   */
  #[Hook('entity_field_access')]
  public function entityFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL) {
    if ($field_definition->getName() == 'field_image' && $operation == 'edit') {
      return AccessResult::forbidden();
    }
    return AccessResult::neutral();
  }

}

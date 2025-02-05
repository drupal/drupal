<?php

declare(strict_types=1);

namespace Drupal\menu_operations_link_test\Hook;

use Drupal\Core\Url;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for menu_operations_link_test.
 */
class MenuOperationsLinkTestHooks {

  /**
   * Implements hook_entity_operation_alter().
   */
  #[Hook('entity_operation_alter')]
  public function entityOperationAlter(array &$operations, EntityInterface $entity): void {
    if (!$entity instanceof MenuLinkContent) {
      return;
    }
    // Alter the title of the edit link appearing in operations menu.
    $operations['edit']['title'] = 'Altered Edit Title';
  }

  /**
   * Implements hook_entity_operation().
   */
  #[Hook('entity_operation')]
  public function entityOperation(EntityInterface $entity): array {
    if (!$entity instanceof MenuLinkContent) {
      return [];
    }
    $operations['custom_operation'] = [
      'title' => 'Custom Home',
      'weight' => 20,
      'url' => Url::fromRoute('<front>'),
    ];
    return $operations;
  }

}

<?php

declare(strict_types=1);

namespace Drupal\layout_builder_override_dependency\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\OrderAfter;
use Drupal\layout_builder_override_dependency\LayoutBuilderEntityViewDisplay;

/**
 * Hook implementations for layout_builder_override_dependency.
 */
class LayoutBuilderOverrideHook {

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter', order: new OrderAfter(['layout_builder']))]
  public function entityTypeAlter(array &$entity_types): void {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    $entity_types['entity_view_display']
      ->setClass(LayoutBuilderEntityViewDisplay::class);
  }

}

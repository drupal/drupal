<?php

declare(strict_types=1);

namespace Drupal\layout_builder_override\Hook;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\layout_builder_override\Entity\EntityViewDisplay;
use Drupal\layout_builder_override\LayoutBuilderEntityViewDisplay;

/**
 * Hook implementations for layout_builder_override.
 */
class LayoutBuilderOverrideHook {

  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types): void {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    if ($this->moduleHandler->moduleExists('layout_builder')) {
      $entity_types['entity_view_display']
        ->setClass(LayoutBuilderEntityViewDisplay::class);
    }
    else {
      $entity_types['entity_view_display']
        ->setClass(EntityViewDisplay::class);
    }
  }

}

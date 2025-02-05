<?php

declare(strict_types=1);

namespace Drupal\layout_builder_extra_field_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for layout_builder_extra_field_test.
 */
class LayoutBuilderExtraFieldTestHooks {

  /**
   * Implements hook_entity_extra_field_info().
   */
  #[Hook('entity_extra_field_info')]
  public function entityExtraFieldInfo(): array {
    $extra['node']['bundle_with_section_field']['display']['layout_builder_extra_field_test'] = [
      'label' => 'New Extra Field',
      'description' => 'New Extra Field description',
      'weight' => 0,
    ];
    return $extra;
  }

}

<?php

declare(strict_types=1);

namespace Drupal\field_layout_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for field_layout_test.
 */
class FieldLayoutTestHooks {

  /**
   * Implements hook_layout_alter().
   */
  #[Hook('layout_alter')]
  public function layoutAlter(&$definitions): void {
    /** @var \Drupal\Core\Layout\LayoutDefinition[] $definitions */
    if (\Drupal::state()->get('field_layout_test.alter_regions') && isset($definitions['layout_onecol'])) {
      $definitions['layout_onecol']->setRegions(['foo' => ['label' => 'Foo']]);
    }
  }

}

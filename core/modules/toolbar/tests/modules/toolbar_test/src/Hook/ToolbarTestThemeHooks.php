<?php

declare(strict_types=1);

namespace Drupal\toolbar_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for toolbar_test.
 */
class ToolbarTestThemeHooks {

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_menu')]
  public function preprocessMenu(&$variables): void {
    // All the standard hook_theme variables should be populated when the
    // Toolbar module is rendering a menu.
    foreach ([
      'menu_name',
      'items',
      'attributes',
    ] as $variable) {
      $variables[$variable];
    }
  }

}

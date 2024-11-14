<?php

declare(strict_types=1);

namespace Drupal\layout_builder_theme_suggestions_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for layout_builder_theme_suggestions_test.
 */
class LayoutBuilderThemeSuggestionsTestHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    // It is necessary to explicitly register the template via hook_theme()
    // because it is added via a module, not a theme.
    return [
      'field__node__body__bundle_with_section_field__default' => [
        'base hook' => 'field',
      ],
    ];
  }

}

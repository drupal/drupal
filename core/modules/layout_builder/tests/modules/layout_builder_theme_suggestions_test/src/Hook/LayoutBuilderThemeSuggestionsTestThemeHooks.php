<?php

declare(strict_types=1);

namespace Drupal\layout_builder_theme_suggestions_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Theme hook implementations for layout_builder_theme_suggestions_test.
 */
class LayoutBuilderThemeSuggestionsTestThemeHooks {

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

  /**
   * Implements hook_preprocess_HOOK() for the list of layouts.
   */
  #[Hook('preprocess_item_list__layouts')]
  public function itemListLayouts(&$variables): void {
    foreach (array_keys($variables['items']) as $layout_id) {
      if (isset($variables['items'][$layout_id]['value']['#title']['icon'])) {
        $variables['items'][$layout_id]['value']['#title']['icon'] = ['#markup' => __METHOD__];
      }
    }
  }

}

<?php

namespace Drupal\stable9\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for stable9.
 */
class Stable9Hooks {
  /**
   * @file
   * Functions to support theming in the Stable9 theme.
   */

  /**
   * Implements hook_preprocess_item_list__search_results().
   *
   * Converts the markup of #empty for search results.
   */
  #[Hook('preprocess_item_list__search_results')]
  public function preprocessItemListSearchResults(&$variables): void {
    if (isset($variables['empty']['#tag'])) {
      $variables['empty']['#tag'] = 'h3';
    }
  }

  /**
   * Implements hook_preprocess_views_view().
   *
   * Adds BC classes that were previously added by the Views module.
   */
  #[Hook('preprocess_views_view')]
  public function preprocessViewsView(&$variables): void {
    if (!empty($variables['attributes']['class'])) {
      $bc_classes = preg_replace('/[^a-zA-Z0-9- ]/', '-', $variables['attributes']['class']);
      $variables['attributes']['class'] = array_merge($variables['attributes']['class'], $bc_classes);
    }
    if (!empty($variables['css_class'])) {
      $existing_classes = explode(' ', $variables['css_class']);
      $bc_classes = preg_replace('/[^a-zA-Z0-9- ]/', '-', $existing_classes);
      $variables['css_class'] = implode(' ', array_merge($existing_classes, $bc_classes));
    }
  }

}

<?php

namespace Drupal\search\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for search.
 */
class SearchThemeHooks {

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_search_result')]
  public function themeSuggestionsSearchResult(array $variables): array {
    return [
      'search_result__' . $variables['plugin_id'],
    ];
  }

  /**
   * Implements hook_preprocess_HOOK() for block templates.
   */
  #[Hook('preprocess_block')]
  public function preprocessBlock(&$variables): void {
    if ($variables['plugin_id'] == 'search_form_block') {
      $variables['attributes']['role'] = 'search';
    }
  }

}

<?php

namespace Drupal\taxonomy\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for taxonomy.
 */
class TaxonomyThemeHooks {

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_taxonomy_term')]
  public function themeSuggestionsTaxonomyTerm(array $variables): array {
    $suggestions = [];
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $variables['elements']['#taxonomy_term'];
    $suggestions[] = 'taxonomy_term__' . $term->bundle();
    $suggestions[] = 'taxonomy_term__' . $term->id();
    return $suggestions;
  }

}

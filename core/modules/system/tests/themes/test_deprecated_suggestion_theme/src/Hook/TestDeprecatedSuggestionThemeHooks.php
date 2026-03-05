<?php

declare(strict_types=1);

namespace Drupal\test_deprecated_suggestion_theme\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for test_deprecated_suggestion_theme.
 */
class TestDeprecatedSuggestionThemeHooks {

  /**
   * Implements hook_theme_suggestions_alter().
   */
  #[Hook('theme_suggestions_alter')]
  public function themeSuggestionsAlter(array &$suggestions, array $variables, $hook): void {
    \Drupal::messenger()->addStatus('test_deprecated_suggestion_theme_theme_suggestions_alter() executed for ' . $hook . '.');
    if ($hook == 'theme_test_suggestion_provided') {
      // Add a deprecated suggestion.
      $suggestions[] = 'theme_test_suggestion_provided__deprecated';
      $suggestions['__DEPRECATED']['theme_test_suggestion_provided__deprecated'] = 'Theme suggestion theme_test_suggestion_provided__deprecated is deprecated in drupal:X.0.0 and is removed from drupal:Y.0.0. This is a test.';
    }
  }

}

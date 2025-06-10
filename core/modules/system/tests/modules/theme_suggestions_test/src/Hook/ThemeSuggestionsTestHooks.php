<?php

declare(strict_types=1);

namespace Drupal\theme_suggestions_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for theme_suggestions_test.
 */
class ThemeSuggestionsTestHooks {

  /**
   * Implements hook_theme_suggestions_alter().
   */
  #[Hook('theme_suggestions_alter')]
  public function themeSuggestionsAlter(array &$suggestions, array &$variables, $hook): void {
    \Drupal::messenger()->addStatus('theme_suggestions_test_theme_suggestions_alter() executed.');
    if ($hook == 'theme_test_general_suggestions') {
      $suggestions[] = $hook . '__module_override';
      $variables['module_hook'] = 'theme_suggestions_test_theme_suggestions_alter';
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter().
   */
  #[Hook('theme_suggestions_theme_test_suggestions_alter')]
  public function themeSuggestionsThemeTestSuggestionsAlter(array &$suggestions, array $variables): void {
    \Drupal::messenger()->addStatus('theme_suggestions_test_theme_suggestions_theme_test_suggestions_alter() executed.');
    $suggestions[] = 'theme_test_suggestions__module_override';
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter().
   */
  #[Hook('theme_suggestions_theme_test_specific_suggestions_alter')]
  public function themeSuggestionsThemeTestSpecificSuggestionsAlter(array &$suggestions, array $variables): void {
    $suggestions[] = 'theme_test_specific_suggestions__variant__foo';
  }

}

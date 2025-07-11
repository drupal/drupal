<?php

declare(strict_types=1);

namespace Drupal\theme_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for theme_test.
 */
class ThemeTestThemeHooks1 {

  /**
   * Implements hook_preprocess_HOOK() for HTML document templates.
   */
  #[Hook('preprocess_html')]
  public function preprocessHtml(&$variables): void {
    $variables['html_attributes']['theme_test_html_attribute'] = 'theme test html attribute value';
    $variables['attributes']['theme_test_body_attribute'] = 'theme test body attribute value';
    $variables['attributes']['theme_test_page_variable'] = 'Page variable is an array.';
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_theme_test_preprocess_suggestions')]
  public function themeSuggestionsThemeTestPreprocessSuggestions($variables): array {
    return [
      'theme_test_preprocess_suggestions__' . $variables['foo'],
    ];
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_theme_test_preprocess_suggestions')]
  public function preprocessThemeTestPreprocessSuggestions(&$variables): void {
    $variables['foo'] = 'Theme hook implementor=theme_theme_test_preprocess_suggestions().';
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_theme_test_suggestion_provided')]
  public function themeSuggestionsThemeTestSuggestionProvided(array $variables): array {
    return [
      'theme_test_suggestion_provided__foo',
    ];
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_node')]
  public function themeSuggestionsNode(array $variables): array {
    $xss = '<script type="text/javascript">alert(\'yo\');</script>';
    $suggestions[] = 'node__' . $xss;
    return $suggestions;
  }

}

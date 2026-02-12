<?php

declare(strict_types=1);

namespace Drupal\test_theme\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\twig_theme_test\TwigThemeTestUtils;

/**
 * Hook implementations for test_theme.
 */
class TestThemeHooks {

  public function __construct(
    protected readonly MessengerInterface $messenger,
  ) {}

  /**
   * Implements hook_preprocess_HOOK() for twig_theme_test_php_variables.
   */
  #[Hook('preprocess_twig_theme_test_php_variables')]
  public function preprocessTwigThemeTestPhpVariables(array &$variables): void {
    $variables['php_values'] = TwigThemeTestUtils::phpValues();
  }

  /**
   * Implements hook_element_info_alter().
   */
  #[Hook('element_info_alter')]
  public function elementInfoAlter(array &$info): void {
    // Decrease the default size of textfields.
    if (isset($info['textfield']['#size'])) {
      $info['textfield']['#size'] = 40;
    }
  }

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(array &$libraries, string $extension): void {
    if ($extension === 'test_theme') {
      $libraries['kitten']['js']['kittens.js'] = [];
    }
  }

  /**
   * Implements hook_theme_test_alter_alter().
   *
   * Tests a theme implementing an alter hook.
   *
   * The confusing function name here is due to this being an implementation of
   * the alter hook invoked when the 'theme_test' module calls
   * \Drupal::moduleHandler->alter('theme_test_alter').
   */
  #[Hook('theme_test_alter_alter')]
  public function themeTestAlterAlter(string &$data): void {
    $data = 'test_theme_theme_test_alter_alter was invoked';
  }

  /**
   * Implements hook_theme_suggestions_alter().
   */
  #[Hook('theme_suggestions_alter')]
  public function themeSuggestionsAlter(array &$suggestions, array &$variables, string $hook): void {
    $this->messenger->addStatus(__METHOD__ . '() executed.');
    // Theme alter hooks run after module alter hooks, so add this theme
    // suggestion to the beginning of the array so that the suggestion added by
    // the theme_suggestions_test module can be picked up when that module is
    // enabled.
    if ($hook == 'theme_test_general_suggestions') {
      array_unshift($suggestions, 'theme_test_general_suggestions__theme_override');
      $variables['theme_hook'] = 'test_theme_theme_suggestions_alter';
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter().
   */
  #[Hook('theme_suggestions_theme_test_suggestions_alter')]
  public function themeSuggestionsThemeTestSuggestionsAlter(array &$suggestions, array $variables): void {
    $this->messenger->addStatus(__METHOD__ . '() executed.');
    // Theme alter hooks run after module alter hooks, so add this theme
    // suggestion to the beginning of the array so that the suggestion added by
    // the theme_suggestions_test module can be picked up when that module is
    // enabled.
    array_unshift($suggestions, 'theme_test_suggestions__theme_override');
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter().
   */
  #[Hook('theme_suggestions_node_alter')]
  public function themeSuggestionsNodeAlter(array &$suggestions, array $variables): void {
    // Add an invalid suggestion to be tested.
    $suggestions[] = 'invalid_theme_suggestions';
    $this->messenger->addStatus(__METHOD__ . '() executed.');
  }

  /**
   * Implements hook_theme_registry_alter().
   */
  #[Hook('theme_registry_alter')]
  public function themeRegistryAlter(array &$registry): void {
    $registry['theme_test_template_test']['variables']['additional'] = 'value';
  }

  /**
   * Implements hook_preprocess_HOOK() for theme_test_preprocess_suggestions.
   *
   * Tests a theme overriding a default hook with a suggestion.
   */
  #[Hook('preprocess_theme_test_preprocess_suggestions')]
  public function preprocessThemeTestPreprocessSuggestions(array &$variables): void {
    $variables['foo'] = 'Theme hook implementor=test_theme_preprocess_theme_test_preprocess_suggestions().';
  }

  /**
   * Implements hook_preprocess_HOOK().
   *
   * Tests a theme overriding a default hook with a suggestion.
   */
  #[Hook('preprocess_theme_test_preprocess_suggestions__suggestion')]
  public function preprocessThemeTestPreprocessSuggestions__suggestion(array &$variables): void {
    $variables['foo'] = 'Suggestion';
  }

  /**
   * Implements hook_preprocess_HOOK().
   *
   * Tests a theme overriding a default hook with a suggestion.
   */
  #[Hook('preprocess_theme_test_preprocess_suggestions__kitten')]
  public function preprocessThemeTestPreprocessSuggestions__kitten(array &$variables): void {
    $variables['foo'] = 'Kitten';
  }

  /**
   * Implements hook_preprocess_HOOK().
   *
   * Tests a theme overriding a default hook with a suggestion.
   */
  #[Hook('preprocess_theme_test_preprocess_suggestions__kitten__flamingo')]
  public function preprocessThemeTestPreprocessSuggestions__kitten__flamingo(array &$variables): void {
    $variables['bar'] = 'Flamingo';
  }

  /**
   * Implements hook_preprocess_HOOK().
   *
   * Tests a preprocess function with suggestions.
   */
  #[Hook('preprocess_theme_test_preprocess_suggestions__kitten__meerkat__tarsier__moose')]
  public function preprocessThemeTestPreprocessSuggestions__kitten__meerkat__tarsier__moose(array &$variables): void {
    $variables['bar'] = 'Moose';
  }

}

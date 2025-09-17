<?php

declare(strict_types=1);

namespace Drupal\theme_test\Hook;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for theme_test.
 */
class ThemeTestHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) : array {
    $items['theme_test'] = [
      'variables' => ['foo' => ''],
      'initial preprocess' => static::class . ':preprocessThemeTest',
    ];
    $items['theme_test_template_test'] = ['template' => 'theme_test.template_test'];
    $items['theme_test_template_test_2'] = ['template' => 'theme_test.template_test'];
    $items['theme_test_suggestion_provided'] = ['variables' => []];
    $items['theme_test_specific_suggestions'] = ['variables' => []];
    $items['theme_test_suggestions'] = ['variables' => []];
    $items['theme_test_general_suggestions'] = [
      'variables' => [
        'module_hook' => 'theme_test_theme',
        'theme_hook' => 'none',
      ],
    ];
    $items['theme_test_foo'] = ['variables' => ['foo' => NULL]];
    $items['theme_test_render_element'] = [
      'render element' => 'elements',
      'initial preprocess' => static::class . ':preprocessThemeTestRenderElement',
    ];
    $items['theme_test_render_element_children'] = ['render element' => 'element'];
    $items['theme_test_preprocess_suggestions'] = ['variables' => ['foo' => '', 'bar' => '']];
    $items['theme_test_preprocess_callback'] = ['variables' => ['foo' => '']];
    $items['theme_test_registered_by_module'] = ['render element' => 'content', 'base hook' => 'container'];
    $items['theme_test_theme_class'] = ['variables' => ['message' => '']];
    $items['theme_test_deprecations_preprocess'] = [
      'variables' => [
        'foo' => '',
        'bar' => '',
        'gaz' => '',
        'set_var' => '',
        'for_var' => '',
        'contents' => [],
      ],
      'initial preprocess' => static::class . ':preprocessThemeTestDeprecationsPreprocess',
    ];
    $items['theme_test_deprecations_child'] = ['variables' => ['foo' => '', 'bar' => '', 'gaz' => '']];
    $items['theme_test_deprecations_hook_theme'] = [
      'variables' => [
        'foo' => '',
        'bar' => '',
        'deprecations' => [
          'foo' => "'foo' is deprecated in drupal:X.0.0 and is removed from drupal:Y.0.0. Use 'new_foo' instead. See https://www.example.com.",
          'bar' => "'bar' is deprecated in drupal:X.0.0 and is removed from drupal:Y.0.0. Use 'new_bar' instead. See https://www.example.com.",
        ],
      ],
    ];
    return $items;
  }

  /**
   * Preprocesses variables for theme_theme_test().
   */
  public function preprocessThemeTest(array &$variables): void {
    $variables['foo'] = 'preprocessThemeTest';
  }

  /**
   * Prepares variables for test render element templates.
   *
   * Default template: theme-test-render-element.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - elements: An associative array containing the properties of the element.
   */
  public function preprocessThemeTestRenderElement(array &$variables): void {
    $variables['attributes']['data-variables-are-preprocessed'] = TRUE;
  }

  /**
   * Prepares variables for theme_test_deprecations_preprocess.
   *
   * Default template: theme-test-deprecations-preprocess.html.twig.
   *
   * @param array $variables
   *   An associative array of variables.
   */
  public function preprocessThemeTestDeprecationsPreprocess(array &$variables): void {
    $variables = array_merge($variables, \Drupal::state()->get('theme_test.theme_test_deprecations_preprocess'));
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_theme_test_preprocess_suggestions__monkey')]
  public function preprocessTestSuggestions(array &$variables): void {
    $variables['foo'] = 'Monkey';
  }

  /**
   * Implements hook_preprocess_HOOK() for HTML document templates.
   */
  #[Hook('preprocess_html')]
  public function preprocessHtml(array &$variables): void {
    $variables['html_attributes']['theme_test_html_attribute'] = 'theme test html attribute value';
    $variables['attributes']['theme_test_body_attribute'] = 'theme test body attribute value';
    $variables['attributes']['theme_test_page_variable'] = 'Page variable is an array.';
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_theme_test_preprocess_suggestions')]
  public function preprocessThemeTestPreprocessSuggestions(array &$variables): void {
    $variables['foo'] = 'Theme hook implementor=theme_theme_test_preprocess_suggestions().';
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_theme_test_preprocess_suggestions')]
  public function themeSuggestionsThemeTestPreprocessSuggestions(array $variables): array {
    return [
      'theme_test_preprocess_suggestions__' . $variables['foo'],
    ];
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

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_theme_test_registered_by_module')]
  public function preprocessThemeTestRegisteredByModule(array &$variables): void {
  }

  /**
   * Implements hook_theme_registry_alter().
   */
  #[Hook('theme_registry_alter')]
  public function themeRegistryAlter(&$registry): void {
    $registry['theme_test_preprocess_callback']['preprocess functions'][] = [
      '\Drupal\theme_test\ThemeTestPreprocess',
      'preprocess',
    ];
  }

  /**
   * Implements hook_page_bottom().
   */
  #[Hook('page_bottom')]
  public function pageBottom(array &$page_bottom): void {
    $page_bottom['theme_test_page_bottom'] = ['#markup' => 'theme test page bottom markup'];
  }

  /**
   * Implements hook_theme_suggestions_alter().
   */
  #[Hook('theme_suggestions_alter')]
  public function themeSuggestionsAlter(array &$suggestions, array $variables, $hook): void {
    \Drupal::messenger()->addStatus('theme_test_theme_suggestions_alter() executed for ' . $hook . '.');
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter().
   */
  #[Hook('theme_suggestions_theme_test_suggestions_alter')]
  public function themeSuggestionsThemeTestSuggestionsAlter(array &$suggestions, array $variables): void {
    \Drupal::messenger()->addStatus('theme_test_theme_suggestions_theme_test_suggestions_alter() executed.');
  }

  /**
   * Implements hook_system_info_alter().
   *
   * @see \Drupal\system\Tests\Theme\ThemeInfoTest::testChanges()
   */
  #[Hook('system_info_alter')]
  public function systemInfoAlter(array &$info, Extension $file, $type): void {
    if ($type == 'theme' && $file->getName() == 'test_theme' && \Drupal::state()->get('theme_test.modify_info_files')) {
      // Add a library to see if the system picks it up.
      $info += ['libraries' => []];
      $info['libraries'][] = 'core/once';
    }
  }

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(array &$libraries, string $extension) : void {
    // Allow test code to simulate library changes in a particular extension by
    // setting a state key in the form `theme_test_library_info_alter
    // $extension`, whose values is an array containing everything that should
    // be recursively merged into the given extension's library definitions.
    $info = \Drupal::state()->get('theme_test_library_info_alter' . " {$extension}");
    if (is_array($info)) {
      $libraries = NestedArray::mergeDeep($libraries, $info);
    }
  }

}

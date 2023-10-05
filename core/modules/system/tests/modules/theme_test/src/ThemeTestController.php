<?php

namespace Drupal\theme_test;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller routines for theme test routes.
 */
class ThemeTestController extends ControllerBase {

  /**
   * Adds stylesheets to test theme .info.yml property processing.
   *
   * @return array
   *   A render array containing custom stylesheets.
   */
  public function testInfoStylesheets() {
    return [
      '#attached' => [
        'library' => [
          'theme_test/theme_stylesheets_override_and_remove_test',
        ],
      ],
    ];
  }

  /**
   * Tests template overriding based on filename.
   *
   * @return array
   *   A render array containing a theme override.
   */
  public function testTemplate() {
    return ['#markup' => \Drupal::theme()->render('theme_test_template_test', [])];
  }

  /**
   * Tests the inline template functionality.
   *
   * @return array
   *   A render array containing an inline template.
   */
  public function testInlineTemplate() {
    $element = [];
    $element['test'] = [
      '#type' => 'inline_template',
      '#template' => 'test-with-context {{ llama }}',
      '#context' => ['llama' => 'social'],
    ];
    return $element;
  }

  /**
   * Calls a theme hook suggestion.
   *
   * @return string
   *   An HTML string containing the themed output.
   */
  public function testSuggestion() {
    return ['#markup' => \Drupal::theme()->render(['theme_test__suggestion', 'theme_test'], [])];
  }

  /**
   * Tests themed output generated in a request listener.
   *
   * @return string
   *   Content in theme_test_output GLOBAL.
   */
  public function testRequestListener() {
    return ['#markup' => $GLOBALS['theme_test_output']];
  }

  /**
   * Menu callback for testing suggestion alter hooks with template files.
   */
  public function suggestionProvided() {
    return ['#theme' => 'theme_test_suggestion_provided'];
  }

  /**
   * Menu callback for testing suggestion alter hooks with template files.
   */
  public function suggestionAlter() {
    return ['#theme' => 'theme_test_suggestions'];
  }

  /**
   * Menu callback for testing hook_theme_suggestions_alter().
   */
  public function generalSuggestionAlter() {
    return ['#theme' => 'theme_test_general_suggestions'];
  }

  /**
   * Menu callback for testing suggestion alter hooks with specific suggestions.
   */
  public function specificSuggestionAlter() {
    return ['#theme' => 'theme_test_specific_suggestions__variant'];
  }

  /**
   * Controller to ensure that no theme is initialized.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response with the theme initialized information.
   */
  public function nonHtml() {
    $theme_initialized = \Drupal::theme()->hasActiveTheme();
    return new JsonResponse(['theme_initialized' => $theme_initialized]);
  }

  /**
   * Controller for testing preprocess functions with theme suggestions.
   */
  public function preprocessSuggestions() {
    return [
      [
        '#theme' => 'theme_test_preprocess_suggestions',
        '#foo' => 'suggestion',
      ],
      [
        '#theme' => 'theme_test_preprocess_suggestions',
        '#foo' => 'kitten',
      ],
      [
        '#theme' => 'theme_test_preprocess_suggestions',
        '#foo' => 'monkey',
      ],
      ['#theme' => 'theme_test_preprocess_suggestions__kitten__flamingo'],
    ];
  }

  /**
   * Controller for testing callable preprocess functions.
   */
  public function preprocessCallback() {
    return [
      '#theme' => 'theme_test_preprocess_callback',
    ];
  }

  /**
   * Controller for testing a namespaced class in a theme.
   */
  public function testThemeClass() {
    return [
      '#theme' => 'theme_test_theme_class',
      '#title' => 'Testing loading a class from a .theme file',
    ];
  }

}

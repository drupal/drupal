<?php

/**
 * @file
 * Contains \Drupal\theme_test\ThemeTestController.
 */

namespace Drupal\theme_test;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller routines for theme test routes.
 */
class ThemeTestController extends ControllerBase {

  /**
   * A theme template that overrides a theme function.
   *
   * @return array
   *   Render array containing a theme.
   */
  public function functionTemplateOverridden() {
    return array(
      '#theme' => 'theme_test_function_template_override',
    );
  }

  /**
   * Adds stylesheets to test theme .info.yml property processing.
   *
   * @return array
   *   A render array containing custom stylesheets.
   */
  public function testInfoStylesheets() {
    return array(
      '#attached' => array(
        'library' => array(
          'theme_test/theme_stylesheets_override_and_remove_test',
        ),
      ),
    );
  }

  /**
   * Tests template overriding based on filename.
   *
   * @return array
   *   A render array containing a theme override.
   */
  public function testTemplate() {
    return ['#markup' => \Drupal::theme()->render('theme_test_template_test', array())];
  }

  /**
   * Tests the inline template functionality.
   *
   * @return array
   *   A render array containing an inline template.
   */
  public function testInlineTemplate() {
    $element = array();
    $element['test'] = array(
      '#type' => 'inline_template',
      '#template' => 'test-with-context {{ llama }}',
      '#context' => array('llama' => 'muuh'),
    );
    return $element;
  }

  /**
   * Calls a theme hook suggestion.
   *
   * @return string
   *   An HTML string containing the themed output.
   */
  public function testSuggestion() {
    return ['#markup' => \Drupal::theme()->render(array('theme_test__suggestion', 'theme_test'), array())];
  }

  /**
   * Tests themed output generated in a request listener.
   *
   * @return string
   *   Content in theme_test_output GLOBAL.
   */
  public function testRequestListener() {
    return ['#markup' =>  $GLOBALS['theme_test_output']];
  }

  /**
   * Menu callback for testing suggestion alter hooks with template files.
   */
  function suggestionProvided() {
    return array('#theme' => 'theme_test_suggestion_provided');
  }

  /**
   * Menu callback for testing suggestion alter hooks with template files.
   */
  function suggestionAlter() {
    return array('#theme' => 'theme_test_suggestions');
  }

  /**
   * Menu callback for testing hook_theme_suggestions_alter().
   */
  function generalSuggestionAlter() {
    return array('#theme' => 'theme_test_general_suggestions');
  }

  /**
   * Menu callback for testing suggestion alter hooks with specific suggestions.
   */
  function specificSuggestionAlter() {
    return array('#theme' => 'theme_test_specific_suggestions__variant');
  }

  /**
   * Menu callback for testing suggestion alter hooks with theme functions.
   */
  function functionSuggestionAlter() {
    return array('#theme' => 'theme_test_function_suggestions');
  }


  /**
   * Menu callback for testing includes with suggestion alter hooks.
   */
  function suggestionAlterInclude() {
    return array('#theme' => 'theme_test_suggestions_include');
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

}

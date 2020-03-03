<?php

namespace Drupal\theme_legacy_test;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for test routes for legacy theme functions.
 *
 * @todo Remove in https://www.drupal.org/project/drupal/issues/3097889
 */
class ThemeTestController extends ControllerBase {

  /**
   * A theme template that overrides a theme function.
   *
   * @return array
   *   Render array containing a theme.
   */
  public function functionTemplateOverridden() {
    return [
      '#theme' => 'theme_test_function_template_override',
    ];
  }

  /**
   * Menu callback for testing suggestion alter hooks with theme functions.
   */
  public function functionSuggestionAlter() {
    return ['#theme' => 'theme_test_function_suggestions'];
  }

  /**
   * Menu callback for testing includes with suggestion alter hooks.
   */
  public function suggestionAlterInclude() {
    return ['#theme' => 'theme_test_suggestions_include'];
  }

}

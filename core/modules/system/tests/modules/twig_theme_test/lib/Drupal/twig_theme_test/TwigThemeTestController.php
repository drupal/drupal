<?php

/**
 * @file
 * Contains \Drupal\twig_theme_test\TwigThemeTestController.
 */

namespace Drupal\twig_theme_test;

/**
 * Controller routines for Twig theme test routes.
 */
class TwigThemeTestController {

  /**
   * Menu callback for testing PHP variables in a Twig template.
   */
  public function phpVariablesRender() {
    return _theme('twig_theme_test_php_variables');
  }

  /**
   * Menu callback for testing translation blocks in a Twig template.
   */
  public function transBlockRender() {
    return array(
      '#theme' => 'twig_theme_test_trans',
    );
  }

}

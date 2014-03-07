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

  /**
   * Menu callback for filters in a Twig template.
   */
  public function testFilterRender() {
    return array(
      '#theme' => 'twig_theme_test_filter',
      '#quote' => array(
        'content' => array('#markup' => 'You can only find truth with logic if you have already found truth without it.'),
        'author' => array('#markup' => 'Gilbert Keith Chesterton'),
        'date' => array('#markup' => '1874-1936'),
      ),
    );
  }

}


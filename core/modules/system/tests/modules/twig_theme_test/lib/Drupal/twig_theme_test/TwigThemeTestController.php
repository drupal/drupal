<?php

/**
 * @file
 * Contains \Drupal\twig_theme_test\TwigThemeTestController.
 */

namespace Drupal\twig_theme_test;

use Drupal\Core\Controller\ControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for Twig theme test routes.
 */
class TwigThemeTestController implements ControllerInterface {

  /**
   * Creates the controller.
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Menu callback for testing PHP variables in a Twig template.
   */
  public function phpVariablesRender() {
    return theme('twig_theme_test_php_variables');
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

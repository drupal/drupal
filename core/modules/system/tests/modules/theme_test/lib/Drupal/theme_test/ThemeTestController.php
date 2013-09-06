<?php

/**
 * @file
 * Contains \Drupal\theme_test\ThemeTestController.
 */

namespace Drupal\theme_test;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for theme test routes.
 */
class ThemeTestController implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Menu callback for testing that a theme template overrides a theme function.
   */
  function functionTemplateOverridden() {
    return array(
      '#theme' => 'theme_test_function_template_override',
    );
  }

}

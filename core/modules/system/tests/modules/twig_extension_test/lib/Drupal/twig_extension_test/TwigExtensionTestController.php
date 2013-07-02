<?php

/**
 * @file
 * Contains \Drupal\twig_extension_test\TwigExtensionTestController.
 */

namespace Drupal\twig_extension_test;

use Drupal\Core\Controller\ControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for Twig extension test routes.
 */
class TwigExtensionTestController implements ControllerInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Menu callback for testing Twig filters in a Twig template.
   */
  public function testFilterRender() {
    return array(
      '#theme' => 'twig_extension_test_filter',
      '#message' => 'Every animal is not a mineral.',
    );
  }

  /**
   * Menu callback for testing Twig functions in a Twig template.
   */
  public function testFunctionRender() {
    return array('#theme' => 'twig_extension_test_function');
  }

}

<?php

/**
 * @file
 * Contains \Drupal\twig_theme_test\ExampleRenderable.
 */

namespace Drupal\twig_theme_test;

use Drupal\Core\Render\RenderableInterface;

/**
 * Provides an example implementation of the RenderableInterface.
 */
class ExampleRenderable implements RenderableInterface {

  /**
   * {@inheritdoc}
   */
  public function toRenderable() {
    return [
      '#markup' => 'Example markup',
    ];
  }

}

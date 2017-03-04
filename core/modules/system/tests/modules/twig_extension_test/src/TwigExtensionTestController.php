<?php

namespace Drupal\twig_extension_test;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Controller routines for Twig extension test routes.
 */
class TwigExtensionTestController {
  use StringTranslationTrait;

  /**
   * Menu callback for testing Twig filters in a Twig template.
   */
  public function testFilterRender() {
    return [
      '#theme' => 'twig_extension_test_filter',
      '#message' => 'Every animal is not a mineral.',
      '#safe_join_items' => [
        '<em>will be escaped</em>',
        $this->t('<em>will be markup</em>'),
        ['#markup' => '<strong>will be rendered</strong>']
      ]
    ];
  }

  /**
   * Menu callback for testing Twig functions in a Twig template.
   */
  public function testFunctionRender() {
    return ['#theme' => 'twig_extension_test_function'];
  }

}

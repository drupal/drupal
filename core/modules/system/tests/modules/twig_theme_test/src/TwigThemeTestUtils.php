<?php

declare(strict_types=1);

namespace Drupal\twig_theme_test;

/**
 * Helper functions used by both test modules and test classes.
 */
class TwigThemeTestUtils {

  /**
   * Helper function to test PHP variables in the Twig engine.
   */
  public static function phpValues(): array {
    // Prefix each variable with "twig_" so that Twig doesn't get confused
    // between a variable and a primitive. Arrays are not tested since they should
    // be a Drupal render array.
    return [
      'twig_null' => [
        'value' => NULL,
        'expected' => '',
      ],
      'twig_bool_false' => [
        'value' => FALSE,
        'expected' => '',
      ],
      'twig_bool_true' => [
        'value' => TRUE,
        'expected' => '1',
      ],
      'twig_int' => [
        'value' => 1,
        'expected' => '1',
      ],
      'twig_int_0' => [
        'value' => 0,
        'expected' => '0',
      ],
      'twig_float' => [
        'value' => 122.34343,
        'expected' => '122.34343',
      ],
      'twig_string' => [
        'value' => 'Hello world!',
        'expected' => 'Hello world!',
      ],
    ];
  }

}

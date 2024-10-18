<?php

namespace Drupal\theme_test;

/**
 * Class to test preprocess callbacks.
 */
class ThemeTestPreprocess {

  /**
   * Preprocess callback for testing preprocess callbacks.
   *
   * @param array $variables
   *   An associative array containing:
   *   - foo: Text for testing preprocess callback.
   */
  public static function preprocess(&$variables) {
    $variables['foo'] = 'Make Drupal full of kittens again!';
  }

}

<?php

/**
 * @file
 * Include file for testing legacy theme functions.
 */

/**
 * Returns HTML for the 'theme_test' theme hook used by tests.
 */
function theme_theme_test($variables) {
  return 'Theme hook implementor=theme_theme_test(). Foo=' . $variables['foo'];
}

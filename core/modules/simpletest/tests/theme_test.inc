<?php

/**
 * Returns HTML for the 'theme_test' theme hook used by tests.
 */
function theme_theme_test($variables) {
  return 'Theme hook implementor=theme_theme_test(). Foo=' . $variables['foo'];
}

/**
 * Preprocesses variables for theme_theme_test().
 */
function template_preprocess_theme_test(&$variables) {
  $variables['foo'] = 'template_preprocess_theme_test';
}

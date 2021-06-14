<?php

/**
 * @file
 * Include file for test module.
 */

/**
 * Preprocesses variables for theme_theme_test().
 */
function template_preprocess_theme_test(&$variables) {
  $variables['foo'] = 'template_preprocess_theme_test';
}

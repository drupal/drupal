<?php

/**
 * @file
 * Include file for test module.
 */

declare(strict_types=1);

/**
 * Preprocesses variables for theme_theme_test().
 */
function template_preprocess_theme_test(&$variables): void {
  $variables['foo'] = 'template_preprocess_theme_test';
}

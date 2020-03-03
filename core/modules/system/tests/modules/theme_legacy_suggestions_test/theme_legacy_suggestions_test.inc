<?php

/**
 * @file
 * Include file for testing theme suggestion hooks for legacy theme functions.
 */

/**
 * Returns HTML for a theme function include test.
 */
function theme_theme_suggestions_test_include($variables) {
  return 'Function suggested via suggestion alter hook found in include file.';
}

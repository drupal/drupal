<?php
// $Id: template.php,v 1.1 2010/03/21 04:05:24 webchick Exp $

/**
 * Tests a theme overriding a suggestion of a base theme hook.
 */
function test_theme_breadcrumb__suggestion($variables) {
  // Tests that preprocess functions for the base theme hook get called even
  // when the suggestion has an implementation.
  return 'test_theme_breadcrumb__suggestion: ' . $variables['theme_test_preprocess_breadcrumb'];
}

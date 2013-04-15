<?php

/**
 * Tests a theme overriding a suggestion of a base theme hook.
 */
function test_theme_theme_test__suggestion($variables) {
  return 'Theme hook implementor=test_theme_theme_test__suggestion(). Foo=' . $variables['foo'];
}

/**
 * Tests a theme implementing an alter hook.
 *
 * The confusing function name here is due to this being an implementation of
 * the alter hook invoked when the 'theme_test' module calls
 * drupal_alter('theme_test_alter').
 */
function test_theme_theme_test_alter_alter(&$data) {
  $data = 'test_theme_theme_test_alter_alter was invoked';
}

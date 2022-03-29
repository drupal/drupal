<?php

/**
 * @file
 * A file to test \Drupal::moduleHandler()->getImplementationInfo() loading includes.
 */

/**
 * Implements hook_test_hook().
 */
function module_test_test_hook() {
  return ['module_test' => 'success!'];
}

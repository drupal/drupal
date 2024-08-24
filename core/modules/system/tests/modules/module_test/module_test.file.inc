<?php

/**
 * @file
 * A file to test \Drupal::moduleHandler()->getImplementationInfo() loading includes.
 */

declare(strict_types=1);

/**
 * Implements hook_test_hook().
 */
function module_test_test_hook() {
  return ['module_test' => 'success!'];
}

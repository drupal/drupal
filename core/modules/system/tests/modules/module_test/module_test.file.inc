<?php

/**
 * @file
 * Install, update and uninstall functions for the module_test module.
 *
 * Provides a hook to test \Drupal::moduleHandler()->getImplementationInfo()
 * loading includes.
 */

declare(strict_types=1);

/**
 * Implements hook_test_hook().
 */
function module_test_test_hook(): array {
  return ['module_test' => 'success!'];
}

/**
 * Implements hook_test_reset_implementations_hook().
 */
function module_test_test_reset_implementations_hook(): string {
  return __FUNCTION__;
}

/**
 * Implements hook_test_reset_implementations_alter().
 */
function module_test_test_reset_implementations_alter(array &$data): void {
  $data[] = __FUNCTION__;
}

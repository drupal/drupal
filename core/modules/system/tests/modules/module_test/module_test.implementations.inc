<?php

/**
 * @file
 * Include file for test module.
 */

declare(strict_types=1);

/**
 * Implements hook_altered_test_hook().
 *
 * @see module_test_module_implements_alter()
 */
function module_test_altered_test_hook() {
  return __FUNCTION__;
}

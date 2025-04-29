<?php

/**
 * @file
 * Include file for test module.
 */

declare(strict_types=1);

/**
 * Implements hook_altered_test_hook().
 *
 * @see module_implements_alter_test_module_implements_alter()
 */
function module_implements_alter_test_altered_test_hook(): string {
  return __FUNCTION__;
}

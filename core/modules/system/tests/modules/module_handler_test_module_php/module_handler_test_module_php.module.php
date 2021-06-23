<?php

/**
 * @file
 * Test module.
 */

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Implements hook_help().
 */
function module_handler_test_module_php_help($route_name, RouteMatchInterface $route_match) {
}

function module_handler_test_module_php_hook($arg) {
  return $arg;
}

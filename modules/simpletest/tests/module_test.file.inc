<?php
// $Id$

/**
 * @file
 * A file to test module_implements() loading includes.
 */

/**
 * Implements hook_test_hook().
 */
function module_test_test_hook() {
  return array('module_test' => 'success!');
}

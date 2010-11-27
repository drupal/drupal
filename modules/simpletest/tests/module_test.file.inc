<?php
// $Id: module_test.file.inc,v 1.3 2010/11/27 20:41:38 dries Exp $

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

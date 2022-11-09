<?php

/**
 * @file
 * Post update functions for test module.
 */

/**
 * Post update that throws an exception.
 */
function post_update_test_failing_post_update_exception() {
  throw new \RuntimeException('This post update fails');
}

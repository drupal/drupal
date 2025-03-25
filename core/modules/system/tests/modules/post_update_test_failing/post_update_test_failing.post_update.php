<?php

/**
 * @file
 * Post update functions for test module.
 */

declare(strict_types=1);

/**
 * Post update that throws an exception.
 */
function post_update_test_failing_post_update_exception(): void {
  throw new \RuntimeException('This post update fails');
}

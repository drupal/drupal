<?php

/**
 * @file
 * Test hooks to check directory ignore when scanning.
 *
 * @todo Remove this module when procedural hooks in modules are no
 *    longer supported https://www.drupal.org/project/drupal/issues/3481555
 */

declare(strict_types=1);

/**
 * Implements hook_ignored_alter().
 */
function ignored_directories_hook_collector_test_ignored_alter(): void {}

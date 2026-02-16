<?php

/**
 * @file
 * Test fixture.
 */

use Drupal\Tests\Core\Update\UpdateOrderingTest;

/**
 * Implements hook_update_dependencies().
 *
 * @see hook_update_dependencies()
 */
function a_module_update_dependencies(): array {
  return UpdateOrderingTest::$updateDependenciesHookReturn;
}

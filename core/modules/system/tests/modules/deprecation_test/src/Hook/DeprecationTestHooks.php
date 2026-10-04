<?php

declare(strict_types=1);

namespace Drupal\deprecation_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Implements hooks for the deprecation test.
 */
class DeprecationTestHooks {

  /**
   * Implements hook_deprecated_hook().
   */
  #[Hook('deprecated_hook')]
  public function deprecatedHook($arg): mixed {
    return $arg;
  }

  /**
   * Implements hook_deprecated_alter_alter().
   */
  #[Hook('deprecated_alter_alter')]
  public function deprecatedAlterAlterFirst(&$data, $context1, $context2): void {
    $data = [$context1, $context2];
  }

}

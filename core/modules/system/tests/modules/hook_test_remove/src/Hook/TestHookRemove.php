<?php

declare(strict_types=1);

namespace Drupal\hook_test_remove\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\RemoveHook;

/**
 * Add a hook here, then remove it with another attribute.
 */
class TestHookRemove {

  /**
   * This hook should not be run because the next hook replaces it.
   */
  #[Hook('custom_hook1')]
  public function hookDoNotRun(): string {
    // This hook should not run.
    return __METHOD__;
  }

  /**
   * This hook should run and prevent custom_hook1.
   */
  #[Hook('custom_hook1')]
  #[RemoveHook(
    'custom_hook1',
    class: TestHookRemove::class,
    method: 'hookDoNotRun'
  )]
  public function hookDoRun(): string {
    // This hook should run.
    return __METHOD__;
  }

}

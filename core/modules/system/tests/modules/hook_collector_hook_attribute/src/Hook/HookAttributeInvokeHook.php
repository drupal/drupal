<?php

declare(strict_types=1);

namespace Drupal\hook_collector_hook_attribute\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Test Hook attribute named arguments.
 */
#[Hook('cache_flush')]
class HookAttributeInvokeHook {

  /**
   * Implements hook_cache_flush().
   */
  public function __invoke(): void {
    // Set a global value we can check in test code.
    $GLOBALS['hook_invoke_method'] = 'hook_invoke_method';
  }

}

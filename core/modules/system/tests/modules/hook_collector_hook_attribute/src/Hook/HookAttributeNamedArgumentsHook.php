<?php

declare(strict_types=1);

namespace Drupal\hook_collector_hook_attribute\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Test Hook attribute named arguments.
 */
#[Hook(hook: 'cache_flush', method: 'flush')]
class HookAttributeNamedArgumentsHook {

  /**
   * Implements hook_cache_flush().
   */
  public function flush(): void {
    // Set a global value we can check in test code.
    $GLOBALS['hook_named_arguments'] = 'hook_named_arguments';
  }

}

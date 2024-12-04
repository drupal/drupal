<?php

declare(strict_types=1);

namespace Drupal\hook_collector_skip_procedural\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for hook_collector_skip_procedural.
 */
class SkipProceduralHooks {

  /**
   * Implements hook_cache_flush().
   */
  #[Hook('cache_flush')]
  public function cacheFlush(): void {
    // Set a global value we can check in test code.
    hook_collector_skip_procedural_custom_function();
    $GLOBALS['skipped_procedural_oop_cache_flush'] = 'skipped_procedural_oop_cache_flush';
  }

}

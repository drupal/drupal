<?php

/**
 * @file
 * Implement hooks.
 */

declare(strict_types=1);

/**
 * This implements cache_flush.
 *
 * We do not have implements so this does not get converted.
 */
function testing_hooks_cache_flush(): void {
  // Set a global value we can check in test code.
  $GLOBALS['profile_procedural'] = 'profile_procedural';
}

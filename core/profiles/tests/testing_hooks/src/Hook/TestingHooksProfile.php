<?php

declare(strict_types=1);

namespace Drupal\testing_hooks\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementation on behalf of another module.
 */
class TestingHooksProfile {

  /**
   * Implements hook_module_preinstall().
   */
  #[Hook('cache_flush')]
  public function flush(): void {
    // Set a global value we can check in test code.
    $GLOBALS['profile_oop'] = 'profile_oop';
  }

}

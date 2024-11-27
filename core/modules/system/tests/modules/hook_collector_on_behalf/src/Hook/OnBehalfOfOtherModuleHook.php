<?php

declare(strict_types=1);

namespace Drupal\hook_collector_on_behalf\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementation on behalf of another module.
 */
class OnBehalfOfOtherModuleHook {

  /**
   * Implements hook_module_preinstall().
   */
  #[Hook('cache_flush', module: 'respond_install_uninstall_hook_test')]
  public function flush(): void {
    // Set a global value we can check in test code.
    $GLOBALS['on_behalf_oop'] = 'on_behalf_oop';
  }

}

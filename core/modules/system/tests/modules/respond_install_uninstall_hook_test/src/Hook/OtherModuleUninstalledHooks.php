<?php

declare(strict_types=1);

namespace Drupal\respond_install_uninstall_hook_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for respond_install_uninstall_hook_test.
 */
class OtherModuleUninstalledHooks {

  /**
   * Implements hook_module_preuninstall().
   */
  #[Hook('module_preuninstall')]
  public function modulePreuninstall(): void {
    // Set a global value we can check in test code.
    $GLOBALS['hook_module_preuninstall'] = 'hook_module_preuninstall';
  }

  /**
   * Implements hook_modules_uninstalled().
   */
  #[Hook('modules_uninstalled')]
  public function modulesUninstall(): void {
    // Set a global value we can check in test code.
    $GLOBALS['hook_modules_uninstalled'] = 'hook_modules_uninstalled';
  }

  /**
   * Implements hook_cache_flush().
   */
  #[Hook('cache_flush')]
  public function cacheFlush(): void {
    // Set a global value we can check in test code.
    $GLOBALS['hook_cache_flush'] = 'hook_cache_flush';
  }

}

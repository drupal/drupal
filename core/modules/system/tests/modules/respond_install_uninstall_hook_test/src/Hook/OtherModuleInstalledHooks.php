<?php

declare(strict_types=1);

namespace Drupal\respond_install_uninstall_hook_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for respond_install_uninstall_hook_test.
 */
class OtherModuleInstalledHooks {

  /**
   * Implements hook_module_preinstall().
   */
  #[Hook('module_preinstall')]
  public function modulePreinstall(): void {
    // Set a global value we can check in test code.
    $GLOBALS['hook_module_preinstall'] = 'hook_module_preinstall';
  }

  /**
   * Implements hook_modules_installed().
   */
  #[Hook('modules_installed')]
  public function modulesInstalled(): void {
    // Set a global value we can check in test code.
    $GLOBALS['hook_modules_installed'] = 'hook_modules_installed';
  }

}

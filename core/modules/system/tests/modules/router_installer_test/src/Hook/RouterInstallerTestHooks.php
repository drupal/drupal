<?php

declare(strict_types=1);

namespace Drupal\router_installer_test\Hook;

use Drupal\Core\Url;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for router_installer_test.
 */
class RouterInstallerTestHooks {

  /**
   * Implements hook_modules_installed().
   */
  #[Hook('modules_installed')]
  public function modulesInstalled($modules): void {
    if (in_array('router_installer_test', $modules, TRUE)) {
      // Ensure a URL can be generated for routes provided by the module during
      // installation.
      \Drupal::state()->set('router_installer_test_modules_installed', Url::fromRoute('router_installer_test.1')->toString());
    }
  }

}

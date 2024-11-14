<?php

declare(strict_types=1);

namespace Drupal\demo_umami_content\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\demo_umami_content\InstallHelper;

/**
 * Hook implementations for demo_umami_content.
 */
class DemoUmamiContentHooks {

  /**
   * Implements hook_module_preinstall().
   */
  #[Hook('module_preinstall')]
  public function modulePreinstall($module) {
    if ($module === 'demo_umami_content' && !\Drupal::service('config.installer')->isSyncing()) {
      // Run before importing config so blocks are created with the correct
      // dependencies.
      \Drupal::classResolver(InstallHelper::class)->importContent();
    }
  }

}

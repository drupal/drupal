<?php

declare(strict_types=1);

namespace Drupal\deprecated_module_test\Hook;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for deprecated_module_test.
 */
class DeprecatedModuleTestHooks {

  /**
   * Implements hook_system_info_alter().
   */
  #[Hook('system_info_alter')]
  public function systemInfoAlter(array &$info, Extension $file, $type): void {
    // Make the 'deprecated_module_contrib' look like it isn't part of core.
    if ($type === 'module' && $info['name'] === 'Deprecated module contrib') {
      $file->origin = 'sites/all';
    }
  }

}

<?php

declare(strict_types=1);

namespace Drupal\test_module_required_by_theme\Hook;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for test_module_required_by_theme.
 */
class TestModuleRequiredByThemeHooks {

  /**
   * Implements hook_system_info_alter().
   */
  #[Hook('system_info_alter')]
  public function systemInfoAlter(array &$info, Extension $file, $type): void {
    if ($file->getName() == 'test_theme_depending_on_modules') {
      $new_info = \Drupal::state()->get('test_theme_depending_on_modules.system_info_alter');
      if ($new_info) {
        $info = $new_info + $info;
      }
    }
  }

}

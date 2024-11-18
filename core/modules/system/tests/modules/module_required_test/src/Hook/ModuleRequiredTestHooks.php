<?php

declare(strict_types=1);

namespace Drupal\module_required_test\Hook;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for module_required_test.
 */
class ModuleRequiredTestHooks {

  /**
   * Implements hook_system_info_alter().
   *
   * Manipulate module dependencies to test dependency chains.
   */
  #[Hook('system_info_alter')]
  public function systemInfoAlter(&$info, Extension $file, $type): void {
    if ($file->getName() == 'module_required_test' && \Drupal::state()->get('module_required_test.hook_system_info_alter')) {
      $info['required'] = TRUE;
      $info['explanation'] = 'Testing hook_system_info_alter()';
    }
  }

}

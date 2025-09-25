<?php

declare(strict_types=1);

namespace Drupal\node_storage_body_field\Hook;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for node_storage_body_field.
 */
class NodeStorageBodyFieldHooks {

  /**
   * Implements hook_system_info_alter().
   */
  #[Hook('system_info_alter')]
  public function systemInfoAlter(array &$info, Extension $file, $type): void {
    // By default, node_storage_body_field is hidden but needs to be un-hidden
    // when installed, so it can be uninstalled.
    if ($file->getType() == 'module' && $file->getName() == 'node_storage_body_field') {
      $info['hidden'] = FALSE;
    }
  }

}

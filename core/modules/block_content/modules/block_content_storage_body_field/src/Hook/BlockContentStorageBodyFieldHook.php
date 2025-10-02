<?php

declare(strict_types=1);

namespace Drupal\block_content_storage_body_field\Hook;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for block_content_storage_body_field.
 */
class BlockContentStorageBodyFieldHook {

  /**
   * Implements hook_system_info_alter().
   */
  #[Hook('system_info_alter')]
  public function systemInfoAlter(array &$info, Extension $file, $type): void {
    // By default, block_content_storage_body_field is hidden but needs to
    // be un-hidden when installed, so it can be uninstalled.
    if ($file->getType() == 'module' && $file->getName() == 'block_content_storage_body_field') {
      $info['hidden'] = FALSE;
    }
  }

}

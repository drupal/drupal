<?php

declare(strict_types=1);

namespace Drupal\field_storage_entity_type_dependency_test\Hook;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations.
 */
class FieldStorageEntityTypeDependencyTestHook {

  /**
   * Implements hook_system_info_alter().
   */
  #[Hook('system_info_alter')]
  public function systemInfoAlter(array &$info, Extension $file, string $type): void {
    if ($file->getName() === 'taxonomy') {
      $info['dependencies'][] = 'drupal:workspaces';
    }
  }

}

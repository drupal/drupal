<?php

declare(strict_types=1);

namespace Drupal\dependency_version_test\Hook;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for dependency_version_test.
 */
class DependencyVersionTestHooks {

  /**
   * Implements hook_system_info_alter().
   */
  #[Hook('system_info_alter')]
  public function systemInfoAlter(&$info, Extension $file, $type): void {
    // Simulate that the core version for Views module contains the string
    // '8.x'.
    if ($file->getName() == 'views') {
      $info['version'] = '9.8.x-dev';
    }
    // Make the test_module require Views 9.2, which should be compatible with
    // core version 9.8.x-dev from above.
    if ($file->getName() == 'test_module') {
      $info['dependencies'] = ['drupal:views (>=9.2)'];
    }
  }

}

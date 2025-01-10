<?php

declare(strict_types=1);

namespace Drupal\unversioned_assets_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for unversioned_assets_test.
 */
class UnversionedAssetsTestHooks {

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(&$libraries, $extension): void {
    if ($extension === 'system') {
      // Remove the version and provide an additional CSS file we can alter the
      // contents of .
      unset($libraries['base']['version']);
      $libraries['base']['css']['component']['public://test.css'] = ['weight' => -10];
    }
  }

}

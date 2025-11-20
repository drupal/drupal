<?php

declare(strict_types=1);

namespace Drupal\ckeditor5_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for ckeditor5_test.
 */
class Ckeditor5TestHooks {

  /**
   * Implements hook_entity_bundle_info_alter().
   */
  #[Hook('entity_bundle_info_alter')]
  public function entityBundleInfoAlter(array &$bundles): void {
    if (isset($bundles['taxonomy_term'])) {
      foreach ($bundles['taxonomy_term'] as $key => $bundle) {
        $bundles['taxonomy_term'][$key]['ckeditor5_link_suggestions'] = TRUE;
      }
    }

    if (isset($bundles['media'])) {
      foreach ($bundles['media'] as $key => $bundle) {
        $bundles['media'][$key]['ckeditor5_link_suggestions'] = TRUE;
      }
    }
  }

}

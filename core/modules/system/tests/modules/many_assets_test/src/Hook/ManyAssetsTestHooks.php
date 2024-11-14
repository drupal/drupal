<?php

declare(strict_types=1);

namespace Drupal\many_assets_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for many_assets_test.
 */
class ManyAssetsTestHooks {

  /**
   * Implements hook_library_info_build().
   */
  #[Hook('library_info_build')]
  public function libraryInfoBuild() {
    $libraries = [];
    // Load the local javascript as an "external" asset varied by query string.
    $base_javascript = \Drupal::request()->getBasePath() . '/' . \Drupal::service('extension.list.module')->getPath('many_assets_test') . '/js/noop.js';
    $base_css = \Drupal::request()->getBasePath() . '/' . \Drupal::service('extension.list.module')->getPath('many_assets_test') . '/css/noop.css';
    // Build a library dependency containing 100 javascript assets.
    for ($i = 1; $i <= 150; $i++) {
      $libraries['many-dependencies']['js'][$base_javascript . '?dep' . $i] = ['type' => 'external'];
      $libraries['many-dependencies']['css']['component'][$base_css . '?dep' . $i] = ['type' => 'external'];
    }
    return $libraries;
  }

}

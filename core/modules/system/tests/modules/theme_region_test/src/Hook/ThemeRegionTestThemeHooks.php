<?php

declare(strict_types=1);

namespace Drupal\theme_region_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for theme_region_test.
 */
class ThemeRegionTestThemeHooks {

  /**
   * Implements hook_preprocess_HOOK() for region templates.
   */
  #[Hook('preprocess_region')]
  public function preprocessRegion(&$variables): void {
    if ($variables['region'] == 'sidebar_first') {
      $variables['attributes']['class'][] = 'new_class';
    }
  }

}

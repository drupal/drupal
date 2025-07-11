<?php

declare(strict_types=1);

namespace Drupal\layout_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for layout_test.
 */
class LayoutTestThemeHooks {

  /**
   * Implements hook_preprocess_HOOK() for layout templates.
   */
  #[Hook('preprocess_layout_test_2col')]
  public function templatePreprocessLayoutTest2col(&$variables): void {
    $variables['region_attributes']['left']->addClass('class-added-by-preprocess');
  }

}

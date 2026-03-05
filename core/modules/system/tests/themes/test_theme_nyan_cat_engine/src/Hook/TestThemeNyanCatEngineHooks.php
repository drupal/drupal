<?php

declare(strict_types=1);

namespace Drupal\test_theme_nyan_cat_engine\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for test_theme_nyan_cat_engine.
 */
class TestThemeNyanCatEngineHooks {
  // cspell:ignore nyan

  /**
   * Implements hook_preprocess_theme_test_template_test().
   */
  #[Hook('preprocess_theme_test_template_test')]
  public function preprocessThemeTestTemplateTest(&$variables): void {
    $variables['kittens'] = 'All of them';
  }

}

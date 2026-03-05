<?php

declare(strict_types=1);

namespace Drupal\test_subsubtheme\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for test_subsubtheme.
 */
class TestSubsubthemeHooks {

  /**
   * Implements hook_preprocess_HOOK() for theme_test_template_test templates.
   */
  #[Hook('preprocess_theme_test_template_test')]
  public function preprocessThemeTestTemplateTest(&$variables): void {
  }

}

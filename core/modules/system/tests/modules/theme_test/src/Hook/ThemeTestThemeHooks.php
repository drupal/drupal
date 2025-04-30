<?php

declare(strict_types=1);

namespace Drupal\theme_test\Hook;

use Drupal\Core\Hook\Attribute\Preprocess;

/**
 * Hook implementations for theme_test.
 */
class ThemeTestThemeHooks {

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Preprocess('theme_test_preprocess_suggestions__monkey')]
  public function preprocessTestSuggestions(&$variables): void {
    $variables['foo'] = 'Monkey';
  }

}

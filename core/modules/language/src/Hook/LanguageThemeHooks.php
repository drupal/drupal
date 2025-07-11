<?php

namespace Drupal\language\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for language.
 */
class LanguageThemeHooks {

  /**
   * Implements hook_preprocess_HOOK() for block templates.
   */
  #[Hook('preprocess_block')]
  public function preprocessBlock(&$variables): void {
    if ($variables['configuration']['provider'] == 'language') {
      $variables['attributes']['role'] = 'navigation';
    }
  }

}

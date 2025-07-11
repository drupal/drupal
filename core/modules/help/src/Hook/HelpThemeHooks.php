<?php

namespace Drupal\help\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for help.
 */
class HelpThemeHooks {
  /**
   * @file
   */

  /**
   * Implements hook_preprocess_HOOK() for block templates.
   */
  #[Hook('preprocess_block')]
  public function preprocessBlock(&$variables): void {
    if ($variables['plugin_id'] == 'help_block') {
      $variables['attributes']['role'] = 'complementary';
    }
  }

}

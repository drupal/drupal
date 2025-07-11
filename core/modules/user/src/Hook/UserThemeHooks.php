<?php

namespace Drupal\user\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for user.
 */
class UserThemeHooks {

  /**
   * Implements hook_preprocess_HOOK() for block templates.
   */
  #[Hook('preprocess_block')]
  public function preprocessBlock(&$variables): void {
    if ($variables['configuration']['provider'] == 'user') {
      switch ($variables['elements']['#plugin_id']) {
        case 'user_login_block':
          $variables['attributes']['role'] = 'form';
          break;
      }
    }
  }

}

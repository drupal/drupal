<?php

namespace Drupal\comment\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for comment.
 */
class CommentThemeHooks {

  /**
   * Implements hook_preprocess_HOOK() for block templates.
   */
  #[Hook('preprocess_block')]
  public function preprocessBlock(&$variables): void {
    if ($variables['configuration']['provider'] == 'comment') {
      $variables['attributes']['role'] = 'navigation';
    }
  }

}

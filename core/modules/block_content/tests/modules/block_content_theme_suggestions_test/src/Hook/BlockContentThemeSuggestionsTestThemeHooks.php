<?php

declare(strict_types=1);

namespace Drupal\block_content_theme_suggestions_test\Hook;

use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for block_content_theme_suggestions_test.
 */
class BlockContentThemeSuggestionsTestThemeHooks {

  /**
   * Implements hook_preprocess_block().
   */
  #[Hook('preprocess_block')]
  public function preprocessBlock(&$variables): void {
    $block_content = $variables['elements']['content']['#block_content'] ?? NULL;
    if ($block_content instanceof BlockContentInterface) {
      $variables['label'] = $block_content->label();
    }
  }

}

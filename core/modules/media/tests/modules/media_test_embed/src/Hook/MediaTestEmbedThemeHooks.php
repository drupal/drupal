<?php

declare(strict_types=1);

namespace Drupal\media_test_embed\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Theme hook implementations for media_test_embed.
 */
class MediaTestEmbedThemeHooks {

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_media_embed_error')]
  public function preprocessMediaEmbedError(&$variables): void {
    $variables['attributes']['class'][] = 'this-error-message-is-themeable';
  }

}

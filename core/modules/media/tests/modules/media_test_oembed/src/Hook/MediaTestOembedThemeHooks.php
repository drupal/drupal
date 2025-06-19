<?php

declare(strict_types=1);

namespace Drupal\media_test_oembed\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Theme hook implementations for media_test_oembed.
 */
class MediaTestOembedThemeHooks {

  /**
   * Implements hook_preprocess_media_oembed_iframe().
   */
  #[Hook('preprocess_media_oembed_iframe')]
  public function preprocessMediaOembedIframe(array &$variables): void {
    if ($variables['resource']->getProvider()->getName() === 'YouTube') {
      $variables['media'] = str_replace('?feature=oembed', '?feature=oembed&pasta=rigatoni', (string) $variables['media']);
    }
    // @see \Drupal\Tests\media\Kernel\OEmbedIframeControllerTest
    $variables['#attached']['library'][] = 'media_test_oembed/frame';
    $variables['#cache']['tags'][] = 'yo_there';
  }

}

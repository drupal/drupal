<?php

declare(strict_types=1);

namespace Drupal\media_test_oembed\Hook;

use Drupal\media\OEmbed\Provider;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for media_test_oembed.
 */
class MediaTestOembedHooks {

  /**
   * Implements hook_oembed_resource_url_alter().
   */
  #[Hook('oembed_resource_url_alter')]
  public function oembedResourceUrlAlter(array &$parsed_url, Provider $provider): void {
    if ($provider->getName() === 'Vimeo') {
      $parsed_url['query']['altered'] = 1;
    }
  }

}

<?php

namespace Drupal\media_test_oembed;

use Drupal\media\OEmbed\UrlResolver as BaseUrlResolver;

/**
 * Overrides the oEmbed URL resolver service for testing purposes.
 */
class UrlResolver extends BaseUrlResolver {

  /**
   * Sets the endpoint URL for an oEmbed resource URL.
   *
   * @param string $url
   *   The resource URL.
   * @param string $endpoint_url
   *   The endpoint URL.
   */
  public static function setEndpointUrl($url, $endpoint_url) {
    $urls = \Drupal::state()->get(static::class, []);
    $urls[$url] = $endpoint_url;
    \Drupal::state()->set(static::class, $urls);
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceUrl($url, $max_width = NULL, $max_height = NULL) {
    $urls = \Drupal::state()->get(static::class, []);

    if (isset($urls[$url])) {
      return $urls[$url];
    }
    return parent::getResourceUrl($url, $max_width, $max_height);
  }

}

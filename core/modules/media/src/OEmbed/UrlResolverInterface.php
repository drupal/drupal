<?php

namespace Drupal\media\OEmbed;

/**
 * Defines the interface for the oEmbed URL resolver service.
 *
 * The URL resolver is responsible for converting oEmbed-compatible media asset
 * URLs into canonical resource URLs, at which an oEmbed representation of the
 * asset can be retrieved.
 */
interface UrlResolverInterface {

  /**
   * Tries to determine the oEmbed provider for a media asset URL.
   *
   * @param string $url
   *   The media asset URL.
   *
   * @return \Drupal\media\OEmbed\Provider
   *   The oEmbed provider for the asset.
   *
   * @throws \Drupal\media\OEmbed\ResourceException
   *   If the provider cannot be determined.
   * @throws \Drupal\media\OEmbed\ProviderException
   *   If tne oEmbed provider causes an error.
   */
  public function getProviderByUrl($url);

  /**
   * Builds the resource URL for a media asset URL.
   *
   * @param string $url
   *   The media asset URL.
   * @param int $max_width
   *   (optional) Maximum width of the oEmbed resource, in pixels.
   * @param int $max_height
   *   (optional) Maximum height of the oEmbed resource, in pixels.
   *
   * @return string
   *   Returns the resource URL corresponding to the given media item URL.
   */
  public function getResourceUrl($url, $max_width = NULL, $max_height = NULL);

}

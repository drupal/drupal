<?php

/**
 * @file
 */

use Drupal\media\OEmbed\Provider;

/**
 * @file
 * Hooks related to Media and its plugins.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters the information provided in \Drupal\media\Annotation\MediaSource.
 *
 * @param array $sources
 *   The array of media source plugin definitions, keyed by plugin ID.
 */
function hook_media_source_info_alter(array &$sources) {
  $sources['youtube']['label'] = t('Youtube rocks!');
}

/**
 * Alters an oEmbed resource URL before it is fetched.
 *
 * @param array $parsed_url
 *   A parsed URL, as returned by \Drupal\Component\Utility\UrlHelper::parse().
 * @param \Drupal\media\OEmbed\Provider $provider
 *   The oEmbed provider for the resource.
 *
 * @see \Drupal\media\OEmbed\UrlResolverInterface::getResourceUrl()
 */
function hook_oembed_resource_url_alter(array &$parsed_url, Provider $provider) {
  // Always serve YouTube videos from youtube-nocookie.com.
  if ($provider->getName() === 'YouTube') {
    $parsed_url['path'] = str_replace('://youtube.com/', '://youtube-nocookie.com/', $parsed_url['path']);
  }
}

/**
 * @} End of "addtogroup hooks".
 */

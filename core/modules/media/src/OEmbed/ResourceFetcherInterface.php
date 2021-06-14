<?php

namespace Drupal\media\OEmbed;

/**
 * Defines an interface for an oEmbed resource fetcher service.
 *
 * The resource fetcher's only responsibility is to retrieve oEmbed resource
 * data from an endpoint URL (i.e., as returned by
 * \Drupal\media\OEmbed\UrlResolverInterface::getResourceUrl()) and return a
 * \Drupal\media\OEmbed\Resource value object.
 */
interface ResourceFetcherInterface {

  /**
   * Fetches an oEmbed resource.
   *
   * @param string $url
   *   Endpoint-specific URL of the oEmbed resource.
   *
   * @return \Drupal\media\OEmbed\Resource
   *   A resource object built from the oEmbed resource data.
   *
   * @see https://oembed.com/#section2
   *
   * @throws \Drupal\media\OEmbed\ResourceException
   *   If the oEmbed endpoint is not reachable or the response returns an
   *   unexpected Content-Type header.
   */
  public function fetchResource($url);

}

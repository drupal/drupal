<?php

namespace Drupal\media\OEmbed;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Site\Settings;
use GuzzleHttp\ClientInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

// cspell:ignore omitscript

/**
 * Converts oEmbed media URLs into endpoint-specific resource URLs.
 */
class UrlResolver implements UrlResolverInterface {

  /**
   * Static cache of discovered oEmbed resource URLs, keyed by canonical URL.
   *
   * A discovered resource URL is the actual endpoint URL for a specific media
   * object, fetched from its canonical URL.
   *
   * @var string[]
   */
  protected $urlCache = [];

  public function __construct(
    protected ProviderRepositoryInterface $providers,
    protected ResourceFetcherInterface $resourceFetcher,
    protected ClientInterface $httpClient,
    protected ModuleHandlerInterface $moduleHandler,
    #[Autowire(service: 'cache.default')]
    protected CacheBackendInterface $cacheBackend,
  ) {}

  /**
   * Runs oEmbed discovery and returns the endpoint URL if successful.
   *
   * @param string $url
   *   The resource's URL.
   *
   * @return string|false
   *   URL of the oEmbed endpoint, or FALSE if the discovery was unsuccessful.
   */
  protected function discoverResourceUrl($url) {
    $patterns = \array_map(static fn ($hostPattern) => \sprintf('{%s}i', $hostPattern), Settings::get('media_oembed_discovery_trusted_host_patterns', []));
    $host = \parse_url($url, \PHP_URL_HOST);
    if (!$host) {
      return FALSE;
    }
    $allowed = FALSE;
    foreach ($patterns as $pattern) {
      if (\preg_match($pattern, $host)) {
        $allowed = TRUE;
        break;
      }
    }
    if (!$allowed) {
      return FALSE;
    }

    try {
      $response = $this->httpClient->get($url);
    }
    catch (ClientExceptionInterface) {
      return FALSE;
    }

    // Only care about HTML responses.
    $response_type = strtolower($response->getHeaderLine('Content-Type'));
    if (!str_contains($response_type, 'text/html')) {
      return FALSE;
    }

    $document = Html::load((string) $response->getBody());
    $xpath = new \DOMXPath($document);

    return $this->findUrl($xpath, 'json') ?: $this->findUrl($xpath, 'xml');
  }

  /**
   * Tries to find the oEmbed URL in a DOM.
   *
   * @param \DOMXPath $xpath
   *   Page HTML as DOMXPath.
   * @param string $format
   *   Format of oEmbed resource. Possible values are 'json' and 'xml'.
   *
   * @return bool|string
   *   A URL to an oEmbed resource or FALSE if not found.
   */
  protected function findUrl(\DOMXPath $xpath, $format) {
    $result = $xpath->query("//link[@type='application/$format+oembed']");
    return $result->length ? $result->item(0)->getAttribute('href') : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getProviderByUrl($url) {
    // Check the URL against every scheme of every endpoint of every provider
    // until we find a match.
    foreach ($this->providers->getAll() as $provider_info) {
      foreach ($provider_info->getEndpoints() as $endpoint) {
        if ($endpoint->matchUrl($url)) {
          return $provider_info;
        }
      }
    }

    $resource_url = $this->discoverResourceUrl($url);
    if ($resource_url) {
      return $this->resourceFetcher->fetchResource($resource_url)->getProvider();
    }

    throw new ResourceException("No matching oEmbed provider found for resource: \"{$url}\"", $url);
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceUrl($url, $max_width = NULL, $max_height = NULL) {
    // Try to get the resource URL from the static cache.
    if (isset($this->urlCache[$url])) {
      return $this->urlCache[$url];
    }

    // Try to get the resource URL from the persistent cache.
    $cache_id = "media:oembed_resource_url:$url:$max_width:$max_height";

    $cached = $this->cacheBackend->get($cache_id);
    if ($cached) {
      $this->urlCache[$url] = $cached->data;
      return $this->urlCache[$url];
    }

    $provider = $this->getProviderByUrl($url);

    $resource_url = $this->getEndpointMatchingUrl($url, $provider);
    $parsed_url = UrlHelper::parse($resource_url);
    if ($max_width) {
      $parsed_url['query']['maxwidth'] = $max_width;
    }
    if ($max_height) {
      $parsed_url['query']['maxheight'] = $max_height;
    }
    // Let other modules alter the resource URL, because some oEmbed providers
    // provide extra parameters in the query string. For example, Instagram also
    // supports the 'omitscript' parameter.
    $this->moduleHandler->alter('oembed_resource_url', $parsed_url, $provider);
    $resource_url = $parsed_url['path'] . '?' . UrlHelper::buildQuery($parsed_url['query']);

    $this->urlCache[$url] = $resource_url;
    $this->cacheBackend->set($cache_id, $resource_url);

    return $resource_url;
  }

  /**
   * For the given media item URL find an endpoint with schemes that match.
   *
   * @param string $url
   *   The media URL used to lookup the matching endpoint.
   * @param \Drupal\media\OEmbed\Provider $provider
   *   The oEmbed provider for the asset.
   *
   * @return string
   *   The resource URL.
   */
  protected function getEndpointMatchingUrl($url, Provider $provider) {
    $endpoints = $provider->getEndpoints();
    $resource_url = reset($endpoints)->buildResourceUrl($url);
    foreach ($endpoints as $endpoint) {
      if ($endpoint->matchUrl($url)) {
        $resource_url = $endpoint->buildResourceUrl($url);
        break;
      }
    }
    return $resource_url ?? reset($endpoints)->buildResourceUrl($url);
  }

}

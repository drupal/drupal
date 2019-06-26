<?php

namespace Drupal\media\OEmbed;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\UseCacheBackendTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Converts oEmbed media URLs into endpoint-specific resource URLs.
 */
class UrlResolver implements UrlResolverInterface {

  use UseCacheBackendTrait;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The OEmbed provider repository service.
   *
   * @var \Drupal\media\OEmbed\ProviderRepositoryInterface
   */
  protected $providers;

  /**
   * The OEmbed resource fetcher service.
   *
   * @var \Drupal\media\OEmbed\ResourceFetcherInterface
   */
  protected $resourceFetcher;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Static cache of discovered oEmbed resource URLs, keyed by canonical URL.
   *
   * A discovered resource URL is the actual endpoint URL for a specific media
   * object, fetched from its canonical URL.
   *
   * @var string[]
   */
  protected $urlCache = [];

  /**
   * Constructs a UrlResolver object.
   *
   * @param \Drupal\media\OEmbed\ProviderRepositoryInterface $providers
   *   The oEmbed provider repository service.
   * @param \Drupal\media\OEmbed\ResourceFetcherInterface $resource_fetcher
   *   The OEmbed resource fetcher service.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   (optional) The cache backend.
   */
  public function __construct(ProviderRepositoryInterface $providers, ResourceFetcherInterface $resource_fetcher, ClientInterface $http_client, ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend = NULL) {
    $this->providers = $providers;
    $this->resourceFetcher = $resource_fetcher;
    $this->httpClient = $http_client;
    $this->moduleHandler = $module_handler;
    $this->cacheBackend = $cache_backend;
    $this->useCaches = isset($cache_backend);
  }

  /**
   * Runs oEmbed discovery and returns the endpoint URL if successful.
   *
   * @param string $url
   *   The resource's URL.
   *
   * @return string|bool
   *   URL of the oEmbed endpoint, or FALSE if the discovery was unsuccessful.
   */
  protected function discoverResourceUrl($url) {
    try {
      $response = $this->httpClient->get($url);
    }
    catch (RequestException $e) {
      return FALSE;
    }

    $document = Html::load((string) $response->getBody());
    $xpath = new \DOMXpath($document);

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
    foreach ($this->providers->getAll() as $provider_name => $provider_info) {
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

    throw new ResourceException('No matching provider found.', $url);
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

    $cached = $this->cacheGet($cache_id);
    if ($cached) {
      $this->urlCache[$url] = $cached->data;
      return $this->urlCache[$url];
    }

    $provider = $this->getProviderByUrl($url);
    $endpoints = $provider->getEndpoints();
    $endpoint = reset($endpoints);
    $resource_url = $endpoint->buildResourceUrl($url);

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
    $this->cacheSet($cache_id, $resource_url);

    return $resource_url;
  }

}

<?php

namespace Drupal\media\OEmbed;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\UseCacheBackendTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

/**
 * Fetches and caches oEmbed resources.
 */
class ResourceFetcher implements ResourceFetcherInterface {

  use UseCacheBackendTrait;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The oEmbed provider repository service.
   *
   * @var \Drupal\media\OEmbed\ProviderRepositoryInterface
   */
  protected $providers;

  /**
   * Constructs a ResourceFetcher object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\media\OEmbed\ProviderRepositoryInterface $providers
   *   The oEmbed provider repository service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   (optional) The cache backend.
   */
  public function __construct(ClientInterface $http_client, ProviderRepositoryInterface $providers, CacheBackendInterface $cache_backend = NULL) {
    $this->httpClient = $http_client;
    $this->providers = $providers;
    $this->cacheBackend = $cache_backend;
    $this->useCaches = isset($cache_backend);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchResource($url) {
    $cache_id = "media:oembed_resource:$url";

    $cached = $this->cacheGet($cache_id);
    if ($cached) {
      return $this->createResource($cached->data, $url);
    }

    try {
      $response = $this->httpClient->get($url);
    }
    catch (RequestException $e) {
      throw new ResourceException('Could not retrieve the oEmbed resource.', $url, [], $e);
    }

    list($format) = $response->getHeader('Content-Type');
    $content = (string) $response->getBody();

    if (strstr($format, 'text/xml') || strstr($format, 'application/xml')) {
      $encoder = new XmlEncoder();
      $data = $encoder->decode($content, 'xml');
    }
    elseif (strstr($format, 'text/javascript') || strstr($format, 'application/json')) {
      $data = Json::decode($content);
    }
    // If the response is neither XML nor JSON, we are in bat country.
    else {
      throw new ResourceException('The fetched resource did not have a valid Content-Type header.', $url);
    }

    $this->cacheSet($cache_id, $data);

    return $this->createResource($data, $url);
  }

  /**
   * Creates a Resource object from raw resource data.
   *
   * @param array $data
   *   The resource data returned by the provider.
   * @param string $url
   *   The URL of the resource.
   *
   * @return \Drupal\media\OEmbed\Resource
   *   A value object representing the resource.
   *
   * @throws \Drupal\media\OEmbed\ResourceException
   *   If the resource cannot be created.
   */
  protected function createResource(array $data, $url) {
    $data += [
      'title' => NULL,
      'author_name' => NULL,
      'author_url' => NULL,
      'provider_name' => NULL,
      'cache_age' => NULL,
      'thumbnail_url' => NULL,
      'thumbnail_width' => NULL,
      'thumbnail_height' => NULL,
      'width' => NULL,
      'height' => NULL,
      'url' => NULL,
      'html' => NULL,
      'version' => NULL,
    ];

    if ($data['version'] !== '1.0') {
      throw new ResourceException("Resource version must be '1.0'", $url, $data);
    }

    // Prepare the arguments to pass to the factory method.
    $provider = $data['provider_name'] ? $this->providers->get($data['provider_name']) : NULL;

    // The Resource object will validate the data we create it with and throw an
    // exception if anything looks wrong. For better debugging, catch those
    // exceptions and wrap them in a more specific and useful exception.
    try {
      switch ($data['type']) {
        case Resource::TYPE_LINK:
          return Resource::link(
            $data['url'],
            $provider,
            $data['title'],
            $data['author_name'],
            $data['author_url'],
            $data['cache_age'],
            $data['thumbnail_url'],
            $data['thumbnail_width'],
            $data['thumbnail_height']
          );

        case Resource::TYPE_PHOTO:
          return Resource::photo(
            $data['url'],
            $data['width'],
            $data['height'],
            $provider,
            $data['title'],
            $data['author_name'],
            $data['author_url'],
            $data['cache_age'],
            $data['thumbnail_url'],
            $data['thumbnail_width'],
            $data['thumbnail_height']
          );

        case Resource::TYPE_RICH:
          return Resource::rich(
            $data['html'],
            $data['width'],
            $data['height'],
            $provider,
            $data['title'],
            $data['author_name'],
            $data['author_url'],
            $data['cache_age'],
            $data['thumbnail_url'],
            $data['thumbnail_width'],
            $data['thumbnail_height']
          );
        case Resource::TYPE_VIDEO:
          return Resource::video(
            $data['html'],
            $data['width'],
            $data['height'],
            $provider,
            $data['title'],
            $data['author_name'],
            $data['author_url'],
            $data['cache_age'],
            $data['thumbnail_url'],
            $data['thumbnail_width'],
            $data['thumbnail_height']
          );

        default:
          throw new ResourceException('Unknown resource type: ' . $data['type'], $url, $data);
      }
    }
    catch (\InvalidArgumentException $e) {
      throw new ResourceException($e->getMessage(), $url, $data, $e);
    }
  }

}

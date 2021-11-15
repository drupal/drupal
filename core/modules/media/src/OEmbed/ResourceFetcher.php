<?php

namespace Drupal\media\OEmbed;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\RequestOptions;

/**
 * Fetches and caches oEmbed resources.
 */
class ResourceFetcher implements ResourceFetcherInterface {

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
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Constructs a ResourceFetcher object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\media\OEmbed\ProviderRepositoryInterface $providers
   *   The oEmbed provider repository service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(ClientInterface $http_client, ProviderRepositoryInterface $providers, CacheBackendInterface $cache_backend = NULL) {
    $this->httpClient = $http_client;
    $this->providers = $providers;
    if (empty($cache_backend)) {
      $cache_backend = \Drupal::cache();
      @trigger_error('Passing NULL as the $cache_backend parameter to ' . __METHOD__ . '() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. See https://www.drupal.org/node/3223594', E_USER_DEPRECATED);
    }
    $this->cacheBackend = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchResource($url) {
    $cache_id = "media:oembed_resource:$url";

    $cached = $this->cacheBackend->get($cache_id);
    if ($cached) {
      return $this->createResource($cached->data, $url);
    }

    try {
      $response = $this->httpClient->request('GET', $url, [
        RequestOptions::TIMEOUT => 5,
      ]);
    }
    catch (TransferException $e) {
      throw new ResourceException('Could not retrieve the oEmbed resource.', $url, [], $e);
    }

    [$format] = $response->getHeader('Content-Type');
    $content = (string) $response->getBody();

    if (strstr($format, 'text/xml') || strstr($format, 'application/xml')) {
      $data = $this->parseResourceXml($content, $url);
    }
    // By default, try to parse the resource data as JSON.
    else {
      $data = Json::decode($content);

      if (json_last_error() !== JSON_ERROR_NONE) {
        throw new ResourceException('Error decoding oEmbed resource: ' . json_last_error_msg(), $url);
      }
    }
    if (empty($data) || !is_array($data)) {
      throw new ResourceException('The oEmbed resource could not be decoded.', $url);
    }

    $this->cacheBackend->set($cache_id, $data);

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

  /**
   * Parses XML resource data.
   *
   * @param string $data
   *   The raw XML for the resource.
   * @param string $url
   *   The resource URL.
   *
   * @return array
   *   The parsed resource data.
   *
   * @throws \Drupal\media\OEmbed\ResourceException
   *   If the resource data could not be parsed.
   */
  protected function parseResourceXml($data, $url) {
    // Enable userspace error handling.
    $was_using_internal_errors = libxml_use_internal_errors(TRUE);
    libxml_clear_errors();

    $content = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
    // Restore the previous error handling behavior.
    libxml_use_internal_errors($was_using_internal_errors);

    $error = libxml_get_last_error();
    if ($error) {
      libxml_clear_errors();
      throw new ResourceException($error->message, $url);
    }
    elseif ($content === FALSE) {
      throw new ResourceException('The fetched resource could not be parsed.', $url);
    }

    // Convert XML to JSON so that the parsed resource has a consistent array
    // structure, regardless of any XML attributes or quirks of the XML parser.
    $data = Json::encode($content);
    return Json::decode($data);
  }

}

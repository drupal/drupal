<?php

namespace Drupal\media\OEmbed;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Retrieves and caches information about oEmbed providers.
 */
class ProviderRepository implements ProviderRepositoryInterface {

  /**
   * How long the provider data should be cached, in seconds.
   *
   * @var int
   */
  protected $maxAge;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * URL of a JSON document which contains a database of oEmbed providers.
   *
   * @var string
   */
  protected $providersUrl;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The key-value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValue;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a ProviderRepository instance.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key-value store factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param int $max_age
   *   (optional) How long the cache data should be kept. Defaults to a week.
   */
  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory, TimeInterface $time, KeyValueFactoryInterface $key_value_factory, LoggerChannelFactoryInterface $logger_factory, int $max_age = 604800) {
    $this->httpClient = $http_client;
    $this->providersUrl = $config_factory->get('media.settings')->get('oembed_providers_url');
    $this->time = $time;
    $this->maxAge = $max_age;
    $this->keyValue = $key_value_factory->get('media');
    $this->logger = $logger_factory->get('media');
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    $current_time = $this->time->getCurrentTime();
    $stored = $this->keyValue->get('oembed_providers');
    // If we have stored data that hasn't yet expired, return that. We need to
    // store the data in a key-value store because, if the remote provider
    // database is unavailable, we'd rather return stale data than throw an
    // exception. This means we cannot use a normal cache backend or expirable
    // key-value store, since those could delete the stale data at any time.
    if ($stored && $stored['expires'] > $current_time) {
      return $stored['data'];
    }

    try {
      $response = $this->httpClient->request('GET', $this->providersUrl);
    }
    catch (ClientExceptionInterface  $e) {
      if (isset($stored['data'])) {
        // Use the stale data to fall back gracefully, but warn site
        // administrators that we used stale data.
        $this->logger->warning('Remote oEmbed providers could not be retrieved due to error: @error. Using previously stored data. This may contain out of date information.', [
          '@error' => $e->getMessage(),
        ]);
        return $stored['data'];
      }
      // We have no previous data and the request failed.
      throw new ProviderException("Could not retrieve the oEmbed provider database from $this->providersUrl", NULL, $e);
    }

    $providers = Json::decode((string) $response->getBody());

    if (!is_array($providers) || empty($providers)) {
      if (isset($stored['data'])) {
        // Use the stale data to fall back gracefully, but as above, warn site
        // administrators that we used stale data.
        $this->logger->warning('Remote oEmbed providers database returned invalid or empty list. Using previously stored data. This may contain out of date information.');
        return $stored['data'];
      }
      // We have no previous data and the current data is corrupt.
      throw new ProviderException('Remote oEmbed providers database returned invalid or empty list.');
    }

    $keyed_providers = [];
    foreach ($providers as $provider) {
      try {
        $name = (string) $provider['provider_name'];
        $keyed_providers[$name] = new Provider($provider['provider_name'], $provider['provider_url'], $provider['endpoints']);
      }
      catch (ProviderException $e) {
        // Skip invalid providers, but log the exception message to help with
        // debugging.
        $this->logger->warning($e->getMessage());
      }
    }

    $this->keyValue->set('oembed_providers', [
      'data' => $keyed_providers,
      'expires' => $current_time + $this->maxAge,
    ]);
    return $keyed_providers;
  }

  /**
   * {@inheritdoc}
   */
  public function get($provider_name) {
    $providers = $this->getAll();

    if (!isset($providers[$provider_name])) {
      throw new \InvalidArgumentException("Unknown provider '$provider_name'");
    }
    return $providers[$provider_name];
  }

}
